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
    { return in_array($action, ['sales','editsale','savesale','stopsale','manage','archiveconfirm','archivebook','edit','savebook','settings','savesettings','orders','operation','dispatchorder','retrynotice','reconcileadmin','fulfilment'], true); }
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
            if (in_array($action,['savesale','stopsale'],true) && $this->service->canManage()) { $this->setVar('shopSale',$this->service->sale()); $this->setVar('shopSaleEditing',true); $this->setVar('shopBooks',$this->service->books(true)); $template='sales_tpl.php'; }
            if ($action === 'savebook' && $this->service->canManage()) { $this->setVar('shopBooks',$this->service->books(true)); $this->setVar('shopBook', $this->input()); $template = 'editor_tpl.php'; }
            if ($action === 'savesettings' && $this->service->canManage()) { $this->setVar('shopSettings', $this->service->settings()); $template = 'settings_tpl.php'; }
            if (in_array($action,['prepare','acceptcombo'],true)) {
                try { $template = $this->cartPage(); } catch (DomainException $ignored) { /* Draft is still displayed by the error template. */ }
            }
        } catch (Throwable $error) {
            error_log('Shop request failed: ' . get_class($error) . ' at ' . basename($error->getFile()) . ':' . $error->getLine());
            http_response_code(503); $this->setVar('shopError', 'temporarily_unavailable'); $this->setVar('shopDraft', $this->input()); $template = 'error_tpl.php';
        }
        if (in_array($template, ['editor_tpl.php', 'settings_tpl.php', 'sales_tpl.php', 'cart_tpl.php'], true)) $this->appendArrayVar('headerParams', '<script defer src="' . htmlspecialchars($this->getResourceUri('shop.js') . '?v=1.006', ENT_QUOTES, 'UTF-8') . '"></script>');
        $this->setVar('shopCsrf', $this->csrf->issue('shop'));
        return $template;
    }
    private function cartPage()
    {
        $cart = $this->cart(); $this->setVar('shopOffer',null); $this->setVar('shopCart', $cart); $this->setVar('shopQuote', null);
        if ($cart) {
            try {
                $this->setVar('shopQuote', $this->service->quote($cart));
                if (($_SERVER['REQUEST_METHOD']??'GET')==='GET' && empty($_SESSION['shop_offer_seen']) && $this->service->ready()) {
                    $offer=$this->service->comboOffer($cart);
                    if ($offer) { $this->setVar('shopOffer',$offer); $_SESSION['shop_offer_seen']=true; }
                }
            }
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
            case 'pageadd':
                $this->post();
                $request=$this->param('request_key');
                if (!preg_match('/^[a-f0-9]{32}$/D',$request)) throw new DomainException('session_expired');
                $id=$this->param('id'); $intent=$this->param('intent');
                if (!in_array($intent,['buy','stay'],true)) throw new DomainException('invalid_cart');
                if (!isset($_SESSION['shop_page_requests'][$request])) {
                    $_SESSION['shop_cart']=$this->service->addFromPage($this->cart(),$id);
                    $_SESSION['shop_page_requests'][$request]=true;
                    $_SESSION['shop_page_requests']=array_slice($_SESSION['shop_page_requests'],-100,null,true);
                    unset($_SESSION['shop_request']);
                }
                if ($intent==='stay' && $this->param('format')==='json') {
                    header('Content-Type: application/json; charset=UTF-8');
                    echo json_encode(['ok'=>true,'message'=>$this->service->text('added_to_cart'),'token'=>$this->csrf->issue('shop'),'request_key'=>bin2hex(random_bytes(16))]);exit;
                }
                if ($intent==='buy') $this->redirect('cart');
                $_SESSION['shop_page_added'][$id]=true;
                header('Location: '.ShopRules::returnPath($this->param('return_url')).'#shop-product-'.$id,true,303);exit;
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
                if (!$cart) unset($_SESSION['shop_offer_seen']);
                $_SESSION['shop_cart'] = ShopRules::cart($cart); unset($_SESSION['shop_request']); $this->redirect('cart');
                break;
            case 'acceptcombo':
                $this->post();
                $_SESSION['shop_cart']=$this->service->acceptCombo($this->cart(),$this->param('id'),$this->param('before_hash'),$this->param('after_hash'));
                unset($_SESSION['shop_request']); $this->redirect('cart'); break;
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
                // Validate the private order link before recovering a stale form.
                $order = $this->service->order($this->param('token'));
                if ($order['payment_state'] !== 'unpaid') $this->redirect('order', ['token' => $this->param('token')]);
                try { $this->post(); }
                catch (DomainException $error) {
                    if ($error->getMessage() !== 'session_expired') throw $error;
                    // Only display the order; never replay a provider request without CSRF.
                    $this->redirect('order', ['token' => $this->param('token')]);
                }
                $this->service->reconcile($this->param('token')); $this->redirect('order', ['token' => $this->param('token')]); break;
            case 'order':
                $order = $this->service->order($this->param('token'));
                // The private link identifies the order, never the payment outcome.
                // Ask the provider using the stored intent, including on its return URL.
                if ($order['payment_state'] === 'unpaid' && $order['intent_id'] !== '') {
                    try { $order = $this->service->reconcile($this->param('token')); }
                    catch (Throwable $error) { error_log('Shop payment confirmation temporarily unavailable'); }
                }
                $this->setVar('shopOrder', $order); $this->setVar('shopToken', $this->param('token')); return 'order_tpl.php';
            case 'editsale':
            case 'sales':
                $this->service->requireManager(); $sale=$this->service->sale(); $this->setVar('shopSale',$sale); $this->setVar('shopSaleEditing',$action==='editsale' || !$sale['revision']); $this->setVar('shopBooks',$this->service->books(true)); return 'sales_tpl.php';
            case 'savesale':
                $this->post(); $this->service->saveSale($this->input()); $this->redirect('sales'); break;
            case 'stopsale':
                $this->post(); $this->service->stopSale($this->input()); $this->redirect('sales'); break;
            case 'manage':
                $all = $this->param('archived') === '1';
                $books = $this->service->books(true);
                $this->setVar('shopShowArchived', $all);
                $this->setVar('shopBooks', $all ? $books : array_values(array_filter($books, fn($book) => $book['status'] !== 'archived'))); return 'manage_tpl.php';
            case 'archiveconfirm':
                $book = $this->service->book($this->param('id'), true);
                if (!$book) throw new DomainException('book_unavailable');
                $this->setVar('shopBook', $book); return 'archive_tpl.php';
            case 'archivebook':
                $this->post(); $this->service->archiveBook($this->input()); $this->redirect('manage'); break;
            case 'edit':
                $this->service->requireManager();
                $this->setVar('shopBooks',$this->service->books(true));
                $this->setVar('shopBook', $this->param('id') ? $this->service->book($this->param('id'), true) : ['id' => bin2hex(random_bytes(16)), 'revision' => 0,'kind'=>$this->param('kind')==='combo'?'combo':'book']); return 'editor_tpl.php';
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
