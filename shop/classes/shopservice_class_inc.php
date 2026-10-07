<?php
/** Book sales, quantity-based shipping costs and physical order fulfilment.
 * Payment truth belongs to payment-service; physical dispatch remains explicit.
 * @author Derek Keats <derek@dkeats.com>
 */
if (empty($GLOBALS['kewl_entry_point_run'])) die('No direct access');
require_once __DIR__ . '/shoprules.php';
require_once __DIR__ . '/shopsalerules.php';
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
    public function sale()
    {
        $row = $this->store->one('settings', 'sale');
        return $row ? json_decode($row['settings_json'], true, 512, JSON_THROW_ON_ERROR) + ['revision'=>(int)$row['revision']]
            : ['revision'=>0, 'enabled'=>false, 'title'=>'', 'description'=>'', 'image_url'=>'', 'button_label'=>'', 'percent'=>40, 'book_ids'=>[], 'starts_at'=>0, 'ends_at'=>0];
    }
    public function saveSale(array $input)
    {
        $this->requireManager();
        return $this->store->transaction(function () use ($input) {
            $old=$this->sale(); $this->revision($input, $old);
            $ids=$input['book_ids']??[];
            if (!is_array($ids) || count($ids)>500) throw new DomainException('invalid_sale_books');
            $ids=array_values(array_unique($ids));
            foreach ($ids as $id) {
                if (!is_string($id)) throw new DomainException('invalid_sale_books');
                $book=$this->store->one('books', $this->id($id));
                if (!$book || $book['status']==='archived') throw new DomainException('invalid_sale_books');
            }
            $starts=ShopSaleRules::timestamp($input['starts_at']??''); $ends=ShopSaleRules::timestamp($input['ends_at']??'');
            if ($ends <= $starts) throw new DomainException('invalid_sale_dates');
            $enabled=($input['enabled']??'')==='1';
            if ($enabled && (!$ids || $ends<=time())) throw new DomainException('invalid_sale_dates');
            $image=$this->string($input['image_url']??'',1500,true);
            if ($image!=='') {
                $site=parse_url($this->getObject('altconfig','config')->getSiteRoot()); $url=parse_url($image);
                if (!$url || ($url['scheme']??'')!=='https' || ($url['host']??'')!==($site['host']??'') || isset($url['user']) || isset($url['pass'])) throw new DomainException('invalid_image');
            }
            $sale=['enabled'=>$enabled,'title'=>$this->string($input['title']??'',191),
                'description'=>$this->string($input['description']??'',5000,true),'image_url'=>$image,
                'button_label'=>$this->string($input['button_label']??'',80),
                'percent'=>ShopRules::integer($input['percent']??'',1,99),'book_ids'=>$ids,'starts_at'=>$starts,'ends_at'=>$ends];
            $row=['settings_json'=>json_encode($sale,JSON_THROW_ON_ERROR),'revision'=>$old['revision']+1];
            if ($old['revision']) $this->store->save('settings','sale',$row); else $this->store->add('settings',['id'=>'sale']+$row);
            return $this->sale();
        });
    }
    public function stopSale(array $input)
    {
        $this->requireManager();
        return $this->store->transaction(function () use ($input) {
            $sale=$this->sale(); $this->revision($input,$sale);
            if (!$sale['revision']) return;
            $revision=$sale['revision']; unset($sale['revision']); $sale['enabled']=false;
            $this->store->save('settings','sale',['settings_json'=>json_encode($sale,JSON_THROW_ON_ERROR),'revision'=>$revision+1]);
        });
    }
    public function saleStatus(array $sale)
    {
        if (empty($sale['enabled'])) return 'sale_inactive';
        if (time() < $sale['starts_at']) return 'sale_scheduled';
        return time() >= $sale['ends_at'] ? 'sale_ended' : 'sale_active';
    }
    /** Combo definitions live alongside optional sale settings; product IDs stay stable. */
    public function combo($id)
    {
        $row=$this->store->one('settings',$id);
        return $row ? json_decode($row['settings_json'],true,512,JSON_THROW_ON_ERROR) : null;
    }
    private function comboBook(array $book, array $sale, int $now)
    {
        $definition=$this->combo($book['id']);
        if (!$definition) return $book;
        $book['kind']='combo'; $book['book_ids']=$definition['book_ids'];
        $book['cross_sell']=!empty($definition['cross_sell']);
        $book['components']=[]; $book['stock']=1000000; $separate=0;
        foreach ($definition['book_ids'] as $id) {
            $child=$this->store->one('books',$id);
            if (!$child || $child['status']!=='published' || $this->combo($id)) { $book['stock']=0; continue; }
            $book['stock']=min($book['stock'],max(0,(int)$child['stock']-$this->store->reserved($id,$now)));
            $separate+=ShopSaleRules::price($child,$sale,$now)??(int)$child['price_minor'];
            $book['components'][]=['book_id'=>$id,'title'=>$child['title'],'isbn'=>$child['isbn'],'quantity'=>1];
        }
        $book['saving_minor']=max(0,$separate-($book['sale_price_minor']??(int)$book['price_minor']));
        return $book;
    }
    /** Only upgrade a partially represented combo; never duplicate an existing combo's books. */
    public function comboOffer(array $cart, $onlyId=null)
    {
        $cart=ShopRules::cart($cart); if (!$cart) return null;
        $before=$this->quote($cart); $covered=[];
        foreach ($cart as $id=>$quantity) {
            $definition=$this->combo($id);
            if ($definition) foreach ($definition['book_ids'] as $child) $covered[$child]=true;
        }
        foreach ($this->books() as $combo) {
            if (($onlyId!==null && $combo['id']!==$onlyId) || empty($combo['cross_sell']) || isset($cart[$combo['id']]) || $combo['stock']<1 || empty($combo['saving_minor'])) continue;
            $ids=$combo['book_ids']; $present=array_intersect($ids,array_keys($cart));
            if (!$present || count($present)===count($ids) || array_intersect($ids,array_keys($covered))) continue;
            $next=$cart;
            foreach ($present as $id) { if (--$next[$id]===0) unset($next[$id]); }
            $next[$combo['id']]=1;
            try {
                $after=$this->quote($next);
                foreach (ShopRules::inventory($after) as $line) {
                    $book=$this->store->one('books',$line['book_id']);
                    if (!$book || (int)$book['stock']-$this->store->reserved($book['id'],time())<$line['quantity']) throw new DomainException('out_of_stock');
                }
            } catch (DomainException $error) { continue; }
            return ['book'=>$combo,'cart'=>$next,'quote'=>$after,'before_hash'=>$this->quoteHash($before),
                'extra_minor'=>$after['amount_minor']-$before['amount_minor'],
                'shipping_saving_minor'=>max(0,$before['shipping_minor']-$after['shipping_minor'])];
        }
        return null;
    }
    public function acceptCombo(array $cart, $id, $beforeHash, $afterHash)
    {
        return $this->store->transaction(function()use($cart,$id,$beforeHash,$afterHash){
            $offer=$this->comboOffer($cart,$this->id($id));
            if (!$offer || !hash_equals($offer['before_hash'],(string)$beforeHash) || !hash_equals($this->quoteHash($offer['quote']),(string)$afterHash)) throw new DomainException('offer_changed');
            return $offer['cart'];
        });
    }
    public function pricedBook(array $book, ?array $sale=null, ?int $now=null)
    {
        $sale=$sale??$this->sale();
        $book['sale_price_minor']=ShopSaleRules::price($book,$sale,$now??time());
        $book['sale_revision']=$book['sale_price_minor']===null?0:$sale['revision'];
        return $this->comboBook($book,$sale,$now??time());
    }
    public function books($managed = false)
    { if ($managed) $this->requireManager(); $sale=$this->sale(); $now=time(); return array_map(fn($book)=>$this->pricedBook($book,$sale,$now),$this->store->rows('books', $managed ? [] : ['status' => 'published'], 500)); }
    public function book($id, $managed = false)
    {
        if ($managed) $this->requireManager();
        $book = $this->store->one('books', $id);
        return $book && ($managed || $book['status'] === 'published') ? $this->pricedBook($book) : null;
    }
    /** Stop new purchases while retaining products referenced by existing orders. */
    public function archiveBook(array $input)
    {
        $this->requireManager();
        return $this->store->transaction(function () use ($input) {
            $id = $this->id($input['id'] ?? '');
            $book = $this->store->one('books', $id);
            if (!$book) throw new DomainException('book_unavailable');
            $this->revision($input, $book);
            if ($book['status'] !== 'archived') $this->store->save('books', $id, ['status'=>'archived', 'revision'=>(int)$book['revision'] + 1]);
            return $this->store->one('books', $id);
        });
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
            $definition=$this->combo($id);
            $isCombo=$definition!==null || (!$old && ($input['kind']??'')==='combo');
            $ids=[];
            if ($isCombo) {
                $ids=$input['book_ids']??[];
                if (!is_array($ids) || count($ids)<2 || count($ids)>20 || count(array_unique($ids))!==count($ids)) throw new DomainException('invalid_combo');
                foreach ($ids as $child) {
                    if (!is_string($child) || $child===$id || !$this->store->one('books',$this->id($child)) || $this->combo($child)) throw new DomainException('invalid_combo');
                }
                sort($ids);
            }
            $stock = $isCombo ? 0 : ShopRules::integer($input['stock'] ?? '', 0, 1000000);
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
            if ($isCombo) {
                $meta=['settings_json'=>json_encode(['book_ids'=>$ids,'cross_sell'=>($input['cross_sell']??'')==='1'],JSON_THROW_ON_ERROR),'revision'=>$row['revision']];
                if ($definition) $this->store->save('settings',$id,$meta); else $this->store->add('settings',['id'=>$id]+$meta);
            }
            return $this->pricedBook($row);
        });
    }
    public function quote(array $cart, $country = 'ZA')
    {
        $books = [];
        $sale=$this->sale(); $now=time();
        foreach (ShopRules::cart($cart) as $id => $quantity) {
            $book=$this->store->one('books',$id); $books[$id]=$book?$this->pricedBook($book,$sale,$now):null;
        }
        return ShopRules::quote($cart, $books, $country, $this->settings());
    }
    public function addFromPage(array $cart, $id)
    {
        return $this->store->transaction(function()use($cart,$id){
            $id=$this->id($id);$book=$this->book($id);
            if (!$book) throw new DomainException('book_unavailable');
            $cart=ShopRules::cart($cart);$cart[$id]=($cart[$id]??0)+1;$cart=ShopRules::cart($cart);
            // Availability is checked without requiring shipping/checkout configuration.
            $inventory=[];
            foreach ($cart as $productId=>$quantity) {
                $product=$this->book($productId);
                if (!$product || (($product['kind']??'')==='combo' && count($product['components'])!==count($product['book_ids']))) throw new DomainException('book_unavailable');
                foreach ($product['components']??[['book_id'=>$productId,'quantity'=>1]] as $child) $inventory[$child['book_id']]=($inventory[$child['book_id']]??0)+$quantity*$child['quantity'];
            }
            if (array_sum($inventory)>ShopRules::MAX_QUANTITY) throw new DomainException('quantity_unavailable');
            foreach ($inventory as $bookId=>$quantity) {
                $physical=$this->store->one('books',$bookId);
                if (!$physical || (int)$physical['stock']-$this->store->reserved($bookId,time())<$quantity) throw new DomainException('out_of_stock');
            }
            return $cart;
        });
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
            foreach (ShopRules::inventory($quote) as $line) {
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
            foreach (ShopRules::inventory($quote) as $line) {
                $book = $this->store->one('books', $line['book_id']);
                if (!$book || (int)$book['stock'] - $this->store->reserved($line['book_id'], time(), $order['id']) < $line['quantity']) $available = false;
            }
            if ($available) foreach (ShopRules::inventory($quote) as $line) {
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
                foreach (ShopRules::inventory($quote) as $line) {
                    $book = $this->store->one('books', $line['book_id']);
                    if (!$book || (int)$book['stock'] - $this->store->reserved($book['id'], time(), $order['id']) < $line['quantity']) throw new DomainException('out_of_stock');
                }
                foreach (ShopRules::inventory($quote) as $line) {
                    $book = $this->store->one('books', $line['book_id']);
                    $this->store->save('books', $book['id'], ['stock' => (int)$book['stock'] - $line['quantity'], 'revision' => (int)$book['revision'] + 1]);
                }
                $changes += ['fulfilment_state' => 'packing', 'stock_applied' => 1];
            } elseif ($operation === 'restock' && in_array($order['payment_state'], ['refunded', 'reversed'], true)
                && $order['fulfilment_state'] === 'review' && (int)$order['stock_applied'] === 1) {
                $quote = json_decode($order['snapshot'], true, 512, JSON_THROW_ON_ERROR);
                foreach (ShopRules::inventory($quote) as $line) {
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
        $intro = strtr($this->text('email_' . $kind), ['{courier}'=>$order['courier'], '{tracking}'=>$order['tracking']]);
        $body = $intro . "\n\n" . $this->text('reference') . ': ' . $order['id'] . "\n";
        foreach ($quote['lines'] as $line) {
            $body .= $line['quantity'] . ' × ' . $line['title'] . ' — ' . $this->money($line['total_minor']) . "\n";
            foreach ($line['components']??[] as $child) $body .= '  '.($line['quantity']*$child['quantity']).' × '.$child['title']."\n";
        }
        $body .= $this->text('shipping') . ': ' . $this->money($quote['shipping_minor']) . "\n" . $this->text('total') . ': ' . $this->money($quote['amount_minor']) . "\n";
        $body .= $this->url('order', ['token' => $this->token($order['id'])]);
        if ($kind === 'dispatched') $body .= "\n\n" . strtr($this->text('email_shipping_thanks'), ['{site}'=>$this->getObject('altconfig', 'config')->getSiteName()]);
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
