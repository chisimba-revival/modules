<?php
if(empty($GLOBALS['kewl_entry_point_run'])) die();
class payment_service extends controller
{
    private const CSRF='payment_service_web';
    public function init(){
        $this->setLayoutTemplate('payment_layout.php');
        $this->payments=$this->getObject('paymentservice'); $this->catalog=$this->getObject('paymentcatalogservice');
        $this->authorization=$this->getObject('membershipauthorizationservice','membership-service');
        $this->user=$this->getObject('user','security');
        $this->csrf=$this->getObject('nativeauthwebcomposition','security')->build()['csrf'];
    }
    public function requiresLogin($action){return (string)$action!=='yocowebhook';}
    public function dispatch($action){
        switch((string)$action){
            case 'buy': return $this->buy(); case 'fakecheckout': return $this->fakeCheckout();
            case 'deliverfake': return $this->deliverFake();
            case 'yocowebhook': return $this->yocoWebhook();
            case 'return': return $this->returned(); case 'products': return $this->products();
            case 'createproduct': return $this->createProduct(); case 'addprice': return $this->addPrice();
            case 'operations': return $this->operations(); case 'catalogue': return $this->catalogue();
            default:return $this->authorization->can('payment.view')?$this->operations():$this->catalogue();
        }
    }
    private function catalogue($message='',$error=''){
        $products=$this->catalog->listProducts(true); $userId=$this->user->userId();
        $requested=$this->param('product');
        if($requested!=='') $products=array_values(array_filter($products,function($product)use($requested){return (string)$product['code']===$requested;}));
        $admissions=$this->getObject('privateadmissionservice','membership-service');
        $products=array_values(array_filter($products,function($product)use($admissions,$userId){return $product['purpose_type']!=='private_course'||!$admissions->isAdmitted($product['purpose_id'],$userId);}));
        $this->setVar('paymentProducts',$products); $this->common($message,$error); return 'catalogue_tpl.php';
    }
    private function buy(){
        if(!$this->validPost()) return $this->catalogue('','invalid_request');
        $correlation='checkout:'.date('YmdHis').':'.bin2hex(random_bytes(6));
        $provider=$this->payments->providerAvailable('yoco')?'yoco':'fake';
        $result=$this->payments->createIntentFromProduct($this->user->userId(),$this->param('product_code'),$provider,$correlation,$correlation);
        if(empty($result['ok'])) return $this->catalogue('',$result['code']);
        $configuredRoot=$this->getObject('altconfig','config')->getItem('KEWL_SITE_ROOT');
        $siteRoot=rtrim(trim((string)$configuredRoot),'/').'/';
        $started=$this->payments->startCheckout($result['intentId'],array(
            'scenario'=>$this->param('scenario')?:'success',
            'successUrl'=>$siteRoot.'index.php?module=payment-service&action=return&intent_id='.$result['intentId'],
            'cancelUrl'=>$siteRoot.'index.php?module=payment-service&action=return&intent_id='.$result['intentId'],
            'failureUrl'=>$siteRoot.'index.php?module=payment-service&action=return&intent_id='.$result['intentId'],
        ));
        if(empty($started['ok'])) return $this->catalogue('',$started['code']);
        if($provider==='yoco'&&!empty($started['approvalUrl'])) { header('Location: '.$started['approvalUrl'],true,303); exit; }
        return $this->fakeCheckoutPage($result['intentId']);
    }
    private function fakeCheckoutPage($intentId,$message='',$error=''){
        $intent=$this->payments->intent($intentId); if(!$intent||$intent['user_id']!==$this->user->userId()) return $this->catalogue('','intent_not_found');
        $this->setVar('paymentIntent',$intent); $this->common($message,$error); return 'fake_checkout_tpl.php';
    }
    private function fakeCheckout(){
        if(!$this->validPost()) return $this->catalogue('','invalid_request');
        $intent=$this->payments->intent($this->param('intent_id')); if(!$intent||$intent['user_id']!==$this->user->userId()) return $this->catalogue('','intent_not_found');
        $result=$this->payments->runFakeScenario($intent['id'],$this->param('scenario'));
        return $this->returned($intent['id'],!empty($result['ok'])?'fake_processed':'',$result['ok']?'':$result['code']);
    }
    private function returned($intentId=null,$message='',$error=''){
        $intent=$this->payments->intent($intentId?:$this->param('intent_id')); if(!$intent||($intent['user_id']!==$this->user->userId()&&!$this->authorization->can('payment.view'))) return $this->catalogue('','intent_not_found');
        $this->payments->recordBrowserReturn($intent['id'],$intent['provider_reference']);
        $this->setVar('paymentIntent',$intent); $this->common($message,$error); return 'return_tpl.php';
    }
    private function products($message='',$error=''){
        if(!$this->user->isAdmin()) return $this->catalogue('','no_access');
        $this->setVar('paymentProducts',$this->catalog->listProducts(false)); $this->common($message,$error); return 'products_tpl.php';
    }
    private function createProduct(){ if(!$this->validPost()||!$this->user->isAdmin()) return $this->products('','invalid_request'); $result=$this->catalog->createProduct(array('code'=>$this->param('code'),'name'=>$this->param('name'),'purposeType'=>$this->param('purpose_type'),'purposeId'=>$this->param('purpose_id'),'billingPeriod'=>$this->param('billing_period'),'durationMonths'=>$this->param('duration_months'))); return $this->products($result['ok']?$result['code']:'',$result['ok']?'':$result['code']); }
    private function addPrice(){ if(!$this->validPost()||!$this->user->isAdmin()) return $this->products('','invalid_request'); $result=$this->catalog->addPrice($this->param('product_id'),array('versionCode'=>$this->param('version_code'),'amountMinor'=>$this->param('amount_minor'),'currency'=>$this->param('currency'),'effectiveFrom'=>$this->param('effective_from'),'effectiveUntil'=>$this->param('effective_until'))); return $this->products($result['ok']?$result['code']:'',$result['ok']?'':$result['code']); }
    private function operations(){ if(!$this->authorization->can('payment.view')) return $this->catalogue('','no_access'); $this->setVar('paymentOperations',$this->payments->operations()); $this->common('',''); return 'operations_tpl.php'; }
    private function deliverFake(){ if(!$this->validPost()||!$this->user->isAdmin()) return $this->operations(); $this->payments->deliverDelayedFakeEvent($this->param('intent_id')); return $this->operations(); }
    private function yocoWebhook(){
        $raw=file_get_contents('php://input'); $headers=array();
        foreach(array('webhook-id'=>'HTTP_WEBHOOK_ID','webhook-timestamp'=>'HTTP_WEBHOOK_TIMESTAMP','webhook-signature'=>'HTTP_WEBHOOK_SIGNATURE') as $name=>$server) $headers[$name]=(string)($_SERVER[$server]??'');
        $result=$this->payments->receiveProviderEvent('yoco',array('rawBody'=>(string)$raw,'headers'=>$headers));
        $accepted=!empty($result['ok'])||in_array($result['code']??'',array('invalid_provider_event','intent_not_found'),true);
        http_response_code($accepted?200:(!empty($result['retryable'])?503:403)); header('Content-Type: application/json');
        echo json_encode(array('accepted'=>$accepted,'code'=>$result['code']??'webhook_failed'),JSON_UNESCAPED_SLASHES); exit;
    }
    private function common($message,$error){ $this->setVar('paymentCsrf',$this->csrf->issue(self::CSRF)); $this->setVar('paymentMessage',$message); $this->setVar('paymentError',$error); $this->setVar('paymentIsAdmin',$this->user->isAdmin()); }
    private function validPost(){return strtoupper($_SERVER['REQUEST_METHOD']??'GET')==='POST'&&$this->csrf->consume(self::CSRF,$this->param('csrf_token'));}
    private function param($name){$value=$this->getParam($name,null);return is_scalar($value)?trim((string)$value):'';}
}
class_alias('payment_service','payment-service');
?>
