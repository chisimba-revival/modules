<?php
/** Once-off guest contributions using the shared catalogue, payment ledger and outbox.
 * No accounts, memberships or audience subscriptions are created. @author Derek Keats
 */
if(empty($GLOBALS['kewl_entry_point_run']))die('No direct access');
class contributionservice extends ChisimbaObject
{
    public function init()
    {
        $this->orders=$this->getObject('dbcontributions');
        $this->catalog=$this->getObject('paymentcatalogservice');
        $this->config=$this->getObject('dbsysconfig','sysconfig');
    }
    public function enabled() { return $this->config->getValue('PAYMENT_CONTRIBUTIONS_ENABLED','payment-service')==='TRUE'; }
    public function checkoutReady()
    {
        $mode=$this->config->getValue('PAYMENT_YOCO_MODE','payment-service');
        $key=trim((string)$this->config->getValue('PAYMENT_YOCO_CHECKOUT_SECRET_KEY','payment-service'));
        $hook=trim((string)$this->config->getValue('PAYMENT_YOCO_WEBHOOK_SECRET','payment-service'));
        return $this->enabled() && str_starts_with($hook,'whsec_')
            && (($mode==='sandbox'&&str_starts_with($key,'sk_test_'))||($mode==='live'&&str_starts_with($key,'sk_live_')));
    }
    public function products()
    {
        return array_values(array_filter($this->catalog->listProducts(true),static fn($p)=>$p['purpose_type']==='contribution'&&$p['billing_period']==='one_off'&&is_array($p['current_price'])&&$p['current_price']['currency']==='ZAR'));
    }
    /** Token is generated once per form. Repeated submissions resume the same order. */
    public function prepare($code,$name,$email,$token)
    {
        if(!$this->enabled())return array('ok'=>false,'code'=>'unavailable');
        if(!is_string($token)||!preg_match('/^[a-f0-9]{64}$/D',$token))return array('ok'=>false,'code'=>'invalid');
        $existing=$this->orders->byToken($token);if($existing)return array('ok'=>true,'order'=>$existing);
        $name=trim((string)$name);$email=strtolower(trim((string)$email));
        if($name===''||strlen($name)>191||preg_match('/[\x00-\x1f\x7f]/',$name)||strlen($email)>254||!filter_var($email,FILTER_VALIDATE_EMAIL))return array('ok'=>false,'code'=>'invalid');
        $p=$this->catalog->purchasable($code);
        if(!$p||$p['purpose_type']!=='contribution'||$p['billing_period']!=='one_off'||$p['price']['currency']!=='ZAR')return array('ok'=>false,'code'=>'unavailable');
        $row=array('id'=>bin2hex(random_bytes(16)),'access_hash'=>hash('sha256',$token),'email'=>$email,'name'=>$name,'product_code'=>$p['code'],'product_name'=>$p['name'],'price_version'=>$p['price']['version_code'],'amount_minor'=>(int)$p['price']['amount_minor'],'vat_minor'=>(int)($p['price']['vat_minor']??0),'currency'=>$p['price']['currency'],'created_at'=>date('Y-m-d H:i:s'));
        if(!$this->orders->create($row))return array('ok'=>false,'code'=>'unavailable');
        return array('ok'=>true,'order'=>$row);
    }
    /** Internal payment core validation prevents arbitrary guest intents or browser prices. */
    public function matchesIntent(array $values)
    {
        $o=$this->orders->byId($values['purpose_id']??'');if(!$o||$values['user_id']!==null||$values['provider_code']!=='yoco')return false;
        foreach(array('product_code','price_version','currency','amount_minor') as $key)if((string)$values[$key]!== (string)$o[$key])return false;
        return $values['idempotency_key']==='contribution:'.$o['id'];
    }
    public function intentFor(array $order)
    {
        return $this->getObject('dbpaymentintents')->byIdempotency('contribution:'.$order['id']);
    }
    public function createIntent(array $order)
    {
        return $this->getObject('paymentservice')->createIntent(array('userId'=>null,'purposeType'=>'contribution','purposeId'=>$order['id'],'productCode'=>$order['product_code'],'priceVersion'=>$order['price_version'],'amountMinor'=>(int)$order['amount_minor'],'currency'=>$order['currency'],'provider'=>'yoco','idempotencyKey'=>'contribution:'.$order['id'],'correlationId'=>'contribution:'.$order['id']));
    }
    /** Called only after verified payment. Duplicate callbacks reuse the same receipt key. */
    public function confirm(array $intent)
    {
        if($intent['state']!=='succeeded')return array('ok'=>false,'code'=>'not_paid');
        $o=$this->orders->byId($intent['purpose_id']);if(!$o)return array('ok'=>false,'code'=>'order_missing');
        $lang=$this->getObject('language','language');
        $text=$lang->code2Txt('mod_payment_service_contribution_receipt','payment-service');
        $text=strtr($text,array('{name}'=>$o['name'],'{product}'=>$o['product_name'],'{reference}'=>$o['id'],'{net}'=>self::money($o['amount_minor']-$o['vat_minor']),'{vat}'=>self::money($o['vat_minor']),'{total}'=>self::money($o['amount_minor'])));
        return $this->getObject('communicationservice','communications')->queueEmail(array('to'=>$o['email'],'subject'=>$lang->code2Txt('mod_payment_service_contribution_receipt_subject','payment-service'),'text'=>$text,'idempotencyKey'=>'contribution-receipt:'.$o['id'],'metadata'=>array('purpose'=>'contribution_receipt')));
    }
    public static function money($minor) { return 'R'.number_format((int)$minor/100,2,'.',' '); }
}
