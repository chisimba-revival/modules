<?php
/** Public shopping and private order operations. @author Derek Keats <derek@dkeats.com> */
if (empty($GLOBALS['kewl_entry_point_run'])) die('No direct access');
class shop extends controller
{
    private $service; private $csrf;
    public function init()
    {
        $this->service = $this->getObject('shopservice');
        $this->csrf = $this->getObject('nativeauthwebcomposition', 'security')->build()['csrf'];
    }
    public function requiresLogin($action)
    { return in_array($action, ['sales','savesale','stopsale','manage','edit','savebook','settings','savesettings','orders','operation','dispatchorder','retrynotice','reconcileadmin','fulfilment'], true); }
    private function param($key) { $value = $this->getParam($key, ''); return is_string($value) ? $value : ''; }
    private function input()
    {
        $values = array_filter($_POST, 'is_string');
        if (is_array($_POST['bands'] ?? null)) $values['bands'] = array_map(fn($row) => is_array($row) ? array_filter($row, 'is_string') : [], array_slice($_POST['bands'], 0, 21));
        if (is_array($_POST['book_ids'] ?? null)) $values['book_ids'] = array_values(array_filter($_POST['book_ids'], 'is_string'));
        return $values;
    }
    private function post()
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST' || !$this->csrf->consume('shop', $this->param('csrf_token'))) throw new DomainException('session_expired');
    }
    private function redirect($action, array $params = [])
    { header('Location: ' . $this->service->url($action, $params), true, 303); exit; }
    private function cart()
    { return ShopRules::cart(is_array($_SESSION['shop_cart'] ?? null) ? $_SESSION['shop_cart'] : []); }
    private function throttle()
    {
        $abuse = $this->getObject('nativeauthwebcomposition', 'security')->build()['abuse'];
        $context = $this->getObject('registrationguard', 'registration-service')->context($this->param('email'));
        $decision = $abuse->evaluate('shop.order', $context, [
            'issued_at' => $this->param('abuse_issued_at'), 'nonce' => $this->param('abuse_nonce'),
            'signature' => $this->param('abuse_signature'), 'website' => $this->param('website'),
        ], ['minimum_seconds' => 1, 'maximum_seconds' => 3600, 'failure_limit' => 10]);
        if (!$decision->isAllowed() || !$abuse->admit('shop.order', $context, [
            ['dimension' => 'ip', 'seconds' => 3600, 'count' => 20],
            ['dimension' => 'account', 'seconds' => 3600, 'count' => 5],
        ])->isAllowed()) throw new DomainException('rate_limited');
        $times = array_values(array_filter($_SESSION['shop_attempts'] ?? [], fn($at) => $at > time() - 3600));
        if (count($times) >= 10) throw new DomainException('rate_limited');
        $times[] = time(); $_SESSION['shop_attempts'] = $times;
    }
    public function dispatch($action)
    {
        header('Cache-Control: private, no-store'); header('Referrer-Policy: no-referrer'); header('X-Robots-Tag: noindex, nofollow');
        $this->setVar('shopService', $this->service); $this->setVar('shopError', ''); $this->setVar('shopDraft', []);
        try { $template = $this->route((string)$action); }
        catch (DomainException $error) {
            http_response_code($error->getMessage() === 'forbidden' ? 403 : 400);
            $this->setVar('shopError', $error->getMessage()); $this->setVar('shopDraft', $this->input());
            $template = 'error_tpl.php';
            if (in_array($action,['savesale','stopsale'],true) && $this->service->canManage()) { $this->setVar('shopSale',$this->service->sale()); $this->setVar('shopBooks',$this->service->books(true)); $template='sales_tpl.php'; }
            if ($action === 'savebook' && $this->service->canManage()) { $this->setVar('shopBook', $this->input()); $template = 'editor_tpl.php'; }
            if ($action === 'savesettings' && $this->service->canManage()) { $this->setVar('shopSettings', $this->service->settings()); $template = 'settings_tpl.php'; }
            if ($action === 'prepare') {
                try { $template = $this->cartPage(); } catch (DomainException $ignored) { /* Draft is still displayed by the error template. */ }
            }
        } catch (Throwable $error) {
            error_log('Shop request failed: ' . get_class($error) . ' at ' . basename($error->getFile()) . ':' . $error->getLine());
            http_response_code(503); $this->setVar('shopError', 'temporarily_unavailable'); $this->setVar('shopDraft', $this->input()); $template = 'error_tpl.php';
        }
        if (in_array($template, ['editor_tpl.php', 'settings_tpl.php', 'sales_tpl.php'], true)) $this->appendArrayVar('headerParams', '<script defer src="' . htmlspecialchars($this->getResourceUri('shop.js'), ENT_QUOTES, 'UTF-8') . '"></script>');
        $this->setVar('shopCsrf', $this->csrf->issue('shop'));
        return $template;
    }
    private function cartPage()
    {
        $cart = $this->cart(); $this->setVar('shopCart', $cart); $this->setVar('shopQuote', null);
        if ($cart) {
            try { $this->setVar('shopQuote', $this->service->quote($cart)); }
            catch (DomainException $error) { $this->setVar('shopError', $error->getMessage()); }
        }
        if (empty($_SESSION['shop_request'])) $_SESSION['shop_request'] = bin2hex(random_bytes(32));
        $this->setVar('shopRequest', $_SESSION['shop_request']);
        $this->setVar('shopAbuse', $this->getObject('nativeauthwebcomposition', 'security')->build()['abuse']->issueFormEvidence('shop.order'));
        $this->setVar('shopSettings', $this->service->settings());
        return 'cart_tpl.php';
    }
    private function route($action)
    {
        switch ($action) {
            case 'add':
            case 'updatecart':
                $this->post(); $cart = $this->cart();
                if ($action === 'add') {
                    $id = $this->param('id'); if (!$this->service->book($id)) throw new DomainException('book_unavailable');
                    $cart[$id] = ($cart[$id] ?? 0) + ShopRules::integer($this->param('quantity'), 1, ShopRules::MAX_QUANTITY);
                } else {
                    $posted = $_POST['quantity'] ?? null;
                    if (!is_array($posted)) throw new DomainException('invalid_cart');
                    $cart = $this->param('clear_cart') === '1' ? [] : ShopRules::cart($posted);
                    if ($this->param('clear_cart') !== '1') {
                        $remove = $this->param('remove_book');
                        if ($remove !== '') unset($cart[$remove]);
                    }
                }
                $_SESSION['shop_cart'] = ShopRules::cart($cart); unset($_SESSION['shop_request']); $this->redirect('cart');
                break;
            case 'cart': return $this->cartPage();
            case 'prepare':
                $this->post(); $this->throttle();
                $key = $this->param('request_key');
                if (!isset($_SESSION['shop_request']) || !hash_equals($_SESSION['shop_request'], $key)) throw new DomainException('session_expired');
                $order = $this->service->prepare($this->cart(), $this->input(), $key);
                $this->redirect('order', ['token' => $this->service->token($order['id'])]); break;
            case 'checkout':
                $this->post(); $url = $this->service->checkout($this->param('token')); header('Location: ' . $url, true, 303); exit;
            case 'reconcile':
                $this->post(); $this->service->reconcile($this->param('token')); $this->redirect('order', ['token' => $this->param('token')]); break;
            case 'order':
                $this->setVar('shopOrder', $this->service->order($this->param('token'))); $this->setVar('shopToken', $this->param('token')); return 'order_tpl.php';
            case 'sales':
                $this->service->requireManager(); $this->setVar('shopSale',$this->service->sale()); $this->setVar('shopBooks',$this->service->books(true)); return 'sales_tpl.php';
            case 'savesale':
                $this->post(); $this->service->saveSale($this->input()); $this->redirect('sales'); break;
            case 'stopsale':
                $this->post(); $this->service->stopSale($this->input()); $this->redirect('sales'); break;
            case 'manage':
                $this->setVar('shopBooks', $this->service->books(true)); return 'manage_tpl.php';
            case 'edit':
                $this->service->requireManager();
                $this->setVar('shopBook', $this->param('id') ? $this->service->book($this->param('id'), true) : ['id' => bin2hex(random_bytes(16)), 'revision' => 0]); return 'editor_tpl.php';
            case 'savebook':
                $this->post(); $book = $this->service->saveBook($this->input()); $this->redirect('edit', ['id' => $book['id']]); break;
            case 'settings':
                $this->service->requireManager(); $this->setVar('shopSettings', $this->service->settings()); return 'settings_tpl.php';
            case 'savesettings':
                $this->post(); $this->service->saveSettings($this->input()); $this->redirect('settings'); break;
            case 'orders':
                $this->setVar('shopOrders', $this->service->orders()); return 'orders_tpl.php';
            case 'fulfilment':
                $this->post(); $order = $this->service->updateFulfilment($this->input()); $this->redirect('operation', ['id' => $order['id']]); break;
            case 'dispatchorder':
                $this->post(); $order = $this->service->dispatchOrder($this->input()); $this->redirect('operation', ['id' => $order['id']]); break;
            case 'retrynotice':
                $this->post(); $this->service->retryNotice($this->param('id')); $this->redirect('operation', ['id' => $this->param('id')]); break;
            case 'reconcileadmin':
                $this->post(); $order = $this->service->managedOrder($this->param('id')); $this->service->reconcile($this->service->token($order['id'])); $this->redirect('operation', ['id' => $order['id']]); break;
            case 'operation':
                $this->setVar('shopOrder', $this->service->managedOrder($this->param('id'))); $this->setVar('shopManaged', true); return 'order_tpl.php';
            case 'view':
                $book = $this->service->book($this->param('id')); if (!$book) throw new DomainException('not_found');
                $this->setVar('shopBooks', [$book]); return 'catalogue_tpl.php';
            case '': case 'catalogue':
                $this->setVar('shopBooks', $this->service->books()); return 'catalogue_tpl.php';
            default: throw new DomainException('not_found');
        }
    }
}
