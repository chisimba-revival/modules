<?php
/** Book sales, quantity-based shipping costs and physical order fulfilment.
 * Payment truth belongs to payment-service; physical dispatch remains explicit.
 * @author Derek Keats <derek@dkeats.com>
 */
if (empty($GLOBALS['kewl_entry_point_run'])) die('No direct access');
require_once __DIR__ . '/shoprules.php';
class shopservice extends ChisimbaObject
{
    public $store; public $payments;
    public function init()
    { $this->store = $this->getObject('shopstore'); $this->payments = $this->getObject('paymentservice', 'payment-service'); }
    public function text($key)
    { return html_entity_decode($this->getObject('language', 'language')->languageText('mod_shop_' . $key, 'shop'), ENT_QUOTES, 'UTF-8'); }
    public function url($action = '', array $params = [])
    { return $this->uri(['action' => $action] + $params, 'shop'); }
    public function money($cents) { return 'R' . number_format((int)$cents / 100, 2, '.', ' '); }
    public function canManage()
    {
        $user = $this->getObject('user', 'security');
        if (!$user->isLoggedIn()) return false;
        if ($user->isAdmin()) return true;
        $permissions = $this->getObject('permissionservice', 'security');
        $area = $permissions->areaIdForName('chisimba', 'shop');
        $right = $area ? $permissions->rightIdForArea($area, 'manage') : null;
        return (bool)($right && $permissions->isGranted($user->userId(), $right));
    }
    public function requireManager()
    { if (!$this->canManage()) throw new DomainException('forbidden'); }
    public function settings()
    {
        $row = $this->store->one('settings', 'shop');
        if (!$row) throw new RuntimeException('Shop needs Module Catalogue installation');
        return json_decode($row['settings_json'], true, 512, JSON_THROW_ON_ERROR) + ['revision' => (int)$row['revision']];
    }
    public function saveSettings(array $input)
    {
        $this->requireManager();
        return $this->store->transaction(function () use ($input) {
            $old = $this->settings();
            $this->revision($input, $old);
            $bands = [];
            foreach ($input['bands'] ?? [] as $band) {
                if (($band['from'] ?? '') === '' && ($band['amount'] ?? '') === '') continue;
                $bands[] = ['from' => $band['from'] ?? '', 'amount_minor' => ShopRules::money($band['amount'] ?? '')];
            }
            $bands = ShopRules::bands($bands);
            $max = ShopRules::integer($input['max_quantity'] ?? '', 1, ShopRules::MAX_QUANTITY);
            if (end($bands)['from'] > $max) throw new DomainException('invalid_shipping');
            $terms = $this->string($input['terms'] ?? '', 10000, true);
            $enabled = ($input['enabled'] ?? '') === '1';
            if ($enabled && ($terms === '' || !$this->payments->providerAvailable('paystack'))) throw new DomainException('checkout_unavailable');
            $settings = ['enabled' => $enabled, 'terms' => $terms,
                'zones' => ['ZA' => ['enabled' => true, 'max_quantity' => $max, 'bands' => $bands]]];
            $this->store->save('settings', 'shop', ['settings_json' => json_encode($settings, JSON_THROW_ON_ERROR), 'revision' => $old['revision'] + 1]);
            return $this->settings();
        });
    }
    public function books($managed = false)
    { if ($managed) $this->requireManager(); return $this->store->rows('books', $managed ? [] : ['status' => 'published'], 500); }
    public function book($id, $managed = false)
    {
        if ($managed) $this->requireManager();
        $book = $this->store->one('books', $id);
        return $book && ($managed || $book['status'] === 'published') ? $book : null;
    }
    public function saveBook(array $input)
    {
        $this->requireManager();
        return $this->store->transaction(function () use ($input) {
            $id = $this->id($input['id'] ?? '');
            $old = $this->store->one('books', $id);
            $this->revision($input, $old ?? ['revision' => 0]);
            $status = $input['status'] ?? '';
            if (!in_array($status, ['draft', 'published', 'archived'], true)) throw new DomainException('invalid_book');
            $stock = ShopRules::integer($input['stock'] ?? '', 0, 1000000);
            if ($stock < $this->store->reserved($id, time())) throw new DomainException('stock_reserved');
            $image = $this->string($input['image_url'] ?? '', 1500, true);
            if ($image !== '') {
                $root = $this->getObject('altconfig', 'config')->getSiteRoot();
                $url = parse_url($image); $site = parse_url($root);
                if (!$url || ($url['scheme'] ?? '') !== 'https' || ($url['host'] ?? '') !== ($site['host'] ?? '') || isset($url['user']) || isset($url['pass'])) throw new DomainException('invalid_image');
            }
            $row = ['id' => $id, 'title' => $this->string($input['title'] ?? '', 191),
                'isbn' => $this->string($input['isbn'] ?? '', 32, true), 'description' => $this->string($input['description'] ?? '', 20000, true),
                'image_url' => $image, 'price_minor' => ShopRules::money($input['price'] ?? ''),
                'stock' => $stock, 'status' => $status, 'revision' => (int)($old['revision'] ?? 0) + 1];
            if (!$row['price_minor']) throw new DomainException('invalid_money');
            if ($old) { $changes = $row; unset($changes['id']); $this->store->save('books', $id, $changes); }
            else $this->store->add('books', $row);
            return $row;
        });
    }
    public function quote(array $cart, $country = 'ZA')
    {
        $books = [];
        foreach (ShopRules::cart($cart) as $id => $quantity) $books[$id] = $this->book($id);
        return ShopRules::quote($cart, $books, $country, $this->settings());
    }
    public function quoteHash(array $quote) { return hash('sha256', json_encode($quote, JSON_THROW_ON_ERROR)); }
    public function ready()
    { return !empty($this->settings()['enabled']) && $this->payments->providerAvailable('paystack'); }
    /** A session-owned request key makes repeated review submissions one order. */
    public function prepare(array $cart, array $input, $requestKey)
    {
        if (!is_string($requestKey) || !preg_match('/^[a-f0-9]{64}$/D', $requestKey)) throw new DomainException('session_expired');
        return $this->store->transaction(function () use ($cart, $input, $requestKey) {
            $existing = $this->store->rows('orders', ['request_hash' => hash('sha256', $requestKey)], 1)[0] ?? null;
            if ($existing) return $existing;
            if (!$this->ready()) throw new DomainException('checkout_unavailable');
            if (($input['accept_terms'] ?? '') !== '1') throw new DomainException('accept_terms_required');
            $country = $input['country'] ?? '';
            if ($country !== 'ZA') throw new DomainException('country_unavailable');
            $quote = $this->quote($cart, $country);
            if (!hash_equals($this->quoteHash($quote), (string)($input['quote_hash'] ?? ''))) throw new DomainException('price_changed');
            foreach ($quote['lines'] as $line) {
                $book = $this->store->one('books', $line['book_id']);
                if ((int)$book['stock'] - $this->store->reserved($book['id'], time()) < $line['quantity']) throw new DomainException('out_of_stock');
            }
            $email = $this->string($input['email'] ?? '', 254);
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) throw new DomainException('invalid_email');
            $address = [];
            foreach (['name', 'phone', 'address_line', 'suburb', 'city', 'province', 'postal_code'] as $field) {
                $address[$field] = $this->string($input[$field] ?? '', $field === 'address_line' ? 255 : 120, $field === 'suburb');
            }
            if (!preg_match('/^[0-9]{4}$/D', $address['postal_code'])) throw new DomainException('invalid_postcode');
            $address['country'] = 'ZA';
            $quote['terms'] = $this->settings()['terms'];
            $row = ['id' => bin2hex(random_bytes(16)), 'request_hash' => hash('sha256', $requestKey),
                'email' => strtolower($email), 'address_json' => json_encode($address, JSON_THROW_ON_ERROR),
                'snapshot' => json_encode($quote, JSON_THROW_ON_ERROR), 'payment_state' => 'unpaid',
                'fulfilment_state' => 'held', 'stock_applied' => 0, 'hold_until' => time() + 900,
                'intent_id' => '', 'checkout_state' => 'new', 'checkout_url' => '',
                'courier' => '', 'tracking' => '', 'revision' => 1, 'created_at' => time(), 'updated_at' => time()];
            $this->store->add('orders', $row);
            $this->history($row['id'], 'created');
            return $row;
        });
    }
    public function token($id)
    {
        $id = $this->id($id);
        $key = (string)$this->getObject('dbsysconfig', 'sysconfig')->getValue('SHOP_SIGNING_KEY', 'shop');
        if (!preg_match('/^[a-f0-9]{64}$/D', $key)) throw new RuntimeException('Shop signing key unavailable');
        return $id . '.' . hash_hmac('sha256', 'shop-order:' . $id, hex2bin($key));
    }
    public function order($token)
    {
        if (!is_string($token) || !preg_match('/^([a-f0-9]{32})\.[a-f0-9]{64}$/D', $token, $m) || !hash_equals($this->token($m[1]), $token)) throw new DomainException('forbidden');
        $order = $this->store->one('orders', $m[1]);
        if (!$order) throw new DomainException('forbidden');
        return $order;
    }
    public function managedOrder($id)
    { $this->requireManager(); $order = $this->store->one('orders', $this->id($id)); if (!$order) throw new DomainException('not_found'); return $order; }
    public function orders() { $this->requireManager(); return $this->store->recentOrders(); }
    public function orderHistory($id) { $this->managedOrder($id); return $this->store->rows('history', ['order_id' => $id]); }
    public function matchesIntent(array $intent)
    {
        $order = $this->store->one('orders', $intent['purpose_id'] ?? '');
        if (!$order) return false;
        $quote = json_decode($order['snapshot'], true, 512, JSON_THROW_ON_ERROR);
        return ($intent['purpose_type'] ?? '') === 'shop_order' && ($intent['provider_code'] ?? '') === 'paystack'
            && ($intent['user_id'] ?? null) === null && ($intent['product_code'] ?? '') === 'shop-order-' . $order['id']
            && ($intent['price_version'] ?? '') === '1' && ($intent['currency'] ?? '') === 'ZAR'
            && (int)($intent['amount_minor'] ?? 0) === $quote['amount_minor']
            && ($intent['idempotency_key'] ?? '') === 'shop-order:' . $order['id'];
    }
    /** Claim before contacting Paystack. An uncertain request is never silently retried. */
    public function checkout($token)
    {
        $order = $this->order($token);
        $claimed = $this->store->transaction(function () use ($order) {
            $order = $this->store->one('orders', $order['id']);
            if (!$this->ready()) throw new DomainException('checkout_unavailable');
            if ($order['payment_state'] !== 'unpaid' || $order['fulfilment_state'] !== 'held' || (int)$order['hold_until'] <= time()) throw new DomainException('order_expired');
            if ($order['checkout_state'] !== 'new') return $order;
            $quote = json_decode($order['snapshot'], true, 512, JSON_THROW_ON_ERROR);
            $result = $this->payments->createIntent(['userId' => null, 'purposeType' => 'shop_order', 'purposeId' => $order['id'],
                'productCode' => 'shop-order-' . $order['id'], 'priceVersion' => '1', 'amountMinor' => $quote['amount_minor'],
                'currency' => 'ZAR', 'provider' => 'paystack', 'idempotencyKey' => 'shop-order:' . $order['id'], 'correlationId' => 'shop:' . $order['id']]);
            if (empty($result['ok'])) throw new DomainException('checkout_unavailable');
            $order['intent_id'] = $result['intentId']; $order['checkout_state'] = 'requested';
            $this->store->save('orders', $order['id'], ['intent_id' => $order['intent_id'], 'checkout_state' => 'requested']);
            $order['_claimed'] = true;
            return $order;
        });
        if (empty($claimed['_claimed'])) {
            if ($claimed['checkout_state'] === 'ready' && $claimed['checkout_url'] !== '') return $claimed['checkout_url'];
            throw new DomainException('checkout_uncertain');
        }
        $return = $this->url('order', ['token' => $token]);
        $started = $this->payments->startCheckout($claimed['intent_id'], ['email' => $claimed['email'], 'successUrl' => $return, 'cancelUrl' => $return, 'failureUrl' => $return]);
        $url = $started['approvalUrl'] ?? '';
        if (empty($started['ok']) || !is_string($url) || !str_starts_with($url, 'https://')) throw new DomainException('checkout_uncertain');
        $this->store->transaction(function () use ($claimed, $url) {
            $this->store->save('orders', $claimed['id'], ['checkout_state' => 'ready', 'checkout_url' => $url]);
        });
        return $url;
    }
    public function reconcile($token)
    {
        $order = $this->order($token);
        if ($order['intent_id'] !== '') $this->payments->reconcileIntent($order['intent_id']);
        return $this->order($token);
    }
    /** Only the canonical verified payment path invokes this method. */
    public function fulfil(array $intent)
    {
        if (($intent['state'] ?? '') !== 'succeeded' || !$this->matchesIntent($intent)) return ['ok' => false, 'code' => 'invalid_shop_payment'];
        $order = $this->store->transaction(function () use ($intent) {
            $order = $this->store->one('orders', $intent['purpose_id']);
            if ($order['intent_id'] !== $intent['id']) throw new DomainException('invalid_shop_payment');
            if ($order['payment_state'] !== 'unpaid') return $order;
            $quote = json_decode($order['snapshot'], true, 512, JSON_THROW_ON_ERROR);
            $available = $order['fulfilment_state'] === 'held';
            foreach ($quote['lines'] as $line) {
                $book = $this->store->one('books', $line['book_id']);
                if (!$book || (int)$book['stock'] - $this->store->reserved($line['book_id'], time(), $order['id']) < $line['quantity']) $available = false;
            }
            if ($available) foreach ($quote['lines'] as $line) {
                $book = $this->store->one('books', $line['book_id']);
                $this->store->save('books', $book['id'], ['stock' => (int)$book['stock'] - $line['quantity'], 'revision' => (int)$book['revision'] + 1]);
            }
            $changes = ['payment_state' => 'paid', 'fulfilment_state' => $available ? 'packing' : 'review',
                'stock_applied' => $available ? 1 : 0, 'revision' => (int)$order['revision'] + 1, 'updated_at' => time()];
            $this->store->save('orders', $order['id'], $changes); $this->history($order['id'], $available ? 'paid' : 'paid_review');
            return array_merge($order, $changes);
        });
        $this->notify($order, 'paid');
        return ['ok' => true, 'code' => $order['fulfilment_state'] === 'review' ? 'shop_payment_needs_review' : 'shop_order_paid'];
    }
    public function reverse(array $intent)
    {
        if (!$this->matchesIntent($intent) || !in_array($intent['state'] ?? '', ['refunded', 'reversed', 'disputed'], true)) return ['ok' => false, 'code' => 'invalid_shop_payment'];
        $this->store->transaction(function () use ($intent) {
            $order = $this->store->one('orders', $intent['purpose_id']);
            if ($order['intent_id'] !== $intent['id']) throw new DomainException('invalid_shop_payment');
            if ($order['payment_state'] === $intent['state']) return;
            // Returned goods are never automatically added to saleable stock.
            $this->store->save('orders', $order['id'], ['payment_state' => $intent['state'],
                'fulfilment_state' => in_array($order['fulfilment_state'], ['dispatched', 'delivered'], true) ? $order['fulfilment_state'] : 'review',
                'revision' => (int)$order['revision'] + 1, 'updated_at' => time()]);
            $this->history($order['id'], $intent['state']);
        });
        return ['ok' => true, 'code' => 'shop_reversal_recorded'];
    }
    public function dispatchOrder(array $input)
    {
        $this->requireManager();
        $order = $this->store->transaction(function () use ($input) {
            $order = $this->managedOrder($input['id'] ?? ''); $this->revision($input, $order);
            if ($order['payment_state'] !== 'paid' || $order['fulfilment_state'] !== 'packing' || !$this->confirmedPayment($order)) throw new DomainException('cannot_dispatch');
            $changes = ['fulfilment_state' => 'dispatched', 'courier' => $this->string($input['courier'] ?? '', 120),
                'tracking' => $this->string($input['tracking'] ?? '', 191), 'revision' => (int)$order['revision'] + 1, 'updated_at' => time()];
            $this->store->save('orders', $order['id'], $changes); $this->history($order['id'], 'dispatched');
            return array_merge($order, $changes);
        });
        $this->notify($order, 'dispatched');
        return $order;
    }
    /** Explicit physical operations; financial refunds remain with Paystack. */
    public function updateFulfilment(array $input)
    {
        $this->requireManager();
        return $this->store->transaction(function () use ($input) {
            $order = $this->managedOrder($input['id'] ?? ''); $this->revision($input, $order);
            $operation = $input['operation'] ?? '';
            $changes = ['revision' => (int)$order['revision'] + 1, 'updated_at' => time()];
            if ($operation === 'cancel' && $order['payment_state'] === 'unpaid' && $order['fulfilment_state'] === 'held') {
                $changes['fulfilment_state'] = 'cancelled';
            } elseif ($operation === 'delivered' && $order['fulfilment_state'] === 'dispatched') {
                $changes['fulfilment_state'] = 'delivered';
            } elseif ($operation === 'allocate' && $order['payment_state'] === 'paid' && $order['fulfilment_state'] === 'review' && !(int)$order['stock_applied']) {
                if (!$this->confirmedPayment($order)) throw new DomainException('invalid_operation');
                $quote = json_decode($order['snapshot'], true, 512, JSON_THROW_ON_ERROR);
                foreach ($quote['lines'] as $line) {
                    $book = $this->store->one('books', $line['book_id']);
                    if (!$book || (int)$book['stock'] - $this->store->reserved($book['id'], time(), $order['id']) < $line['quantity']) throw new DomainException('out_of_stock');
                }
                foreach ($quote['lines'] as $line) {
                    $book = $this->store->one('books', $line['book_id']);
                    $this->store->save('books', $book['id'], ['stock' => (int)$book['stock'] - $line['quantity'], 'revision' => (int)$book['revision'] + 1]);
                }
                $changes += ['fulfilment_state' => 'packing', 'stock_applied' => 1];
            } elseif ($operation === 'restock' && in_array($order['payment_state'], ['refunded', 'reversed'], true)
                && $order['fulfilment_state'] === 'review' && (int)$order['stock_applied'] === 1) {
                $quote = json_decode($order['snapshot'], true, 512, JSON_THROW_ON_ERROR);
                foreach ($quote['lines'] as $line) {
                    $book = $this->store->one('books', $line['book_id']);
                    if (!$book) throw new DomainException('book_unavailable');
                    $this->store->save('books', $book['id'], ['stock' => (int)$book['stock'] + $line['quantity'], 'revision' => (int)$book['revision'] + 1]);
                }
                $changes += ['fulfilment_state' => 'cancelled', 'stock_applied' => 2];
            } else throw new DomainException('invalid_operation');
            $this->store->save('orders', $order['id'], $changes); $this->history($order['id'], $operation);
            return array_merge($order, $changes);
        });
    }
    public function retryNotice($id)
    {
        $order = $this->managedOrder($id);
        if ($order['payment_state'] === 'paid') $this->notify($order, 'paid');
        if (in_array($order['fulfilment_state'], ['dispatched', 'delivered'], true)) $this->notify($order, 'dispatched');
    }
    private function confirmedPayment(array $order)
    {
        $intent = $this->payments->intent($order['intent_id']);
        return is_array($intent) && $intent['state'] === 'succeeded' && $this->matchesIntent($intent);
    }
    private function notify(array $order, $kind)
    {
        $address = json_decode($order['address_json'], true, 512, JSON_THROW_ON_ERROR);
        $quote = json_decode($order['snapshot'], true, 512, JSON_THROW_ON_ERROR);
        $body = $this->text('email_' . $kind) . "\n" . $this->text('reference') . ': ' . $order['id'] . "\n";
        foreach ($quote['lines'] as $line) $body .= $line['quantity'] . ' × ' . $line['title'] . ' — ' . $this->money($line['total_minor']) . "\n";
        $body .= $this->text('shipping') . ': ' . $this->money($quote['shipping_minor']) . "\n" . $this->text('total') . ': ' . $this->money($quote['amount_minor']) . "\n";
        if ($kind === 'dispatched') $body .= $order['courier'] . ': ' . $order['tracking'] . "\n";
        $body .= $this->url('order', ['token' => $this->token($order['id'])]);
        $result = $this->getObject('communicationservice', 'communications')->queueEmail(['to' => $order['email'], 'toName' => $address['name'],
            'subject' => $this->text('email_' . $kind . '_subject'), 'text' => $body,
            'idempotencyKey' => 'shop:' . $order['id'] . ':' . $kind, 'metadata' => ['purpose' => 'shop_order', 'orderId' => $order['id']]]);
        if (empty($result['ok'])) throw new RuntimeException('Shop notice could not be queued');
    }
    private function history($id, $event)
    {
        $this->store->add('history', ['id' => bin2hex(random_bytes(16)), 'order_id' => $id,
            'event' => $event, 'actor_id' => (string)$this->getObject('user', 'security')->userId(), 'created_at' => time()]);
    }
    private function revision(array $input, array $old)
    { if (ShopRules::integer($input['revision'] ?? '', 0, PHP_INT_MAX) !== (int)$old['revision']) throw new DomainException('changed_elsewhere'); }
    private function id($id)
    { if (!is_string($id) || !preg_match('/^[a-f0-9]{32}$/D', $id)) throw new DomainException('not_found'); return $id; }
    private function string($value, $max, $optional = false)
    {
        if (!is_string($value)) throw new DomainException('invalid_details');
        $value = trim($value);
        if ((!$optional && $value === '') || mb_strlen($value) > $max || preg_match('/[\x00-\x08\x0b\x0c\x0e-\x1f]/', $value)) throw new DomainException('invalid_details');
        return $value;
    }
}
