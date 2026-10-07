<?php
/** Shop dispatch contracts at the shared payment boundary; no network or mail.
 * @author Derek Keats <derek@dkeats.com>
 */
$GLOBALS['kewl_entry_point_run']=true;
class ChisimbaObject {public $objects=[];public function getObject($name,$module=''){return $this->objects[$name];}}
/** @author Derek Keats <derek@dkeats.com> */
class ShopPaymentIntents {
    public $row;
    public function byId($id){return $this->row&&$this->row['id']===$id?$this->row:null;}
    public function byIdempotency($key){return $this->row&&$this->row['idempotency_key']===$key?$this->row:null;}
    public function create($r){$this->row=$r;return true;}
    public function transition($id,$state,$changes){if(!$this->row||$this->row['state']!==$state)return false;$this->row=array_merge($this->row,$changes);return true;}
}
/** @author Derek Keats <derek@dkeats.com> */
class ShopPaymentClaims {
    public $keys=[];
    public function claim($v){$key=$v['provider_event_id'];$duplicate=isset($this->keys[$key]);$this->keys[$key]=true;return ['ok'=>true,'id'=>$key,'duplicate'=>$duplicate];}
    public function complete($id,$code){}
}
/** @author Derek Keats <derek@dkeats.com> */
class ShopPaymentLedger {public function record($v){} public function successfulReferenceForIntent($id){return 'fixture-payment';}}
/** @author Derek Keats <derek@dkeats.com> */
class ShopPaymentAudit {public function append($v){return ['ok'=>true];}}
/** @author Derek Keats <derek@dkeats.com> */
class ShopPaymentUsers {public function findByUserId($id){throw new RuntimeException('Guests must not manufacture an account');}}
/** @author Derek Keats <derek@dkeats.com> */
class ShopPaymentProvider {public $intents; public function isAvailable(){return true;} public function createCheckout($intent,$options){if($this->intents->row['provider_reference']!==$intent['id'])throw new RuntimeException('Reference must be saved before remote request');if(($options['product']['billing_period']??'')!=='one_off')throw new RuntimeException('Shop must be once-off');return ['ok'=>true,'providerReference'=>$intent['id'],'approvalUrl'=>'https://checkout.paystack.com/fixture'];} public function verifyAndNormalize($envelope){return empty($envelope['verified'])?['ok'=>false]:['ok'=>true,'event'=>$envelope['event']];}}
/** @author Derek Keats <derek@dkeats.com> */
class ShopPaymentFulfilment {
    public $issued=0; public $revoked=0; public $core;
    public function matchesIntent($v){return $v['purpose_id']===str_repeat('b',32)&&$v['amount_minor']===23000&&$v['currency']==='ZAR'&&$v['user_id']===null&&$v['provider_code']==='paystack'&&$v['product_code']==='shop-order-fixture'&&$v['price_version']==='v1'&&$v['idempotency_key']==='shop-order:'.str_repeat('b',32);}
    public function fulfil($i){if($this->core->intent($i['id'])['state']!=='succeeded')return ['ok'=>false];$this->issued=1;return ['ok'=>true];}
    public function reverse($i){if(!in_array($this->core->intent($i['id'])['state'],['refunded','reversed','disputed'],true))return ['ok'=>false];$this->revoked++;return ['ok'=>true];}
}
require dirname(__DIR__).'/classes/paymentservice_class_inc.php';
$core=new paymentservice();$intents=new ShopPaymentIntents();$fulfil=new ShopPaymentFulfilment();$fulfil->core=$core;
$core->objects=['dbpaymentintents'=>$intents,'dbpaymentevents'=>new ShopPaymentClaims(),'dbpayments'=>new ShopPaymentLedger(),'dbpaymentsubscriptions'=>new stdClass(),'paymentcatalogservice'=>new stdClass(),'userservice'=>new ShopPaymentUsers(),'accounteventservice'=>new ShopPaymentAudit(),'shopservice'=>$fulfil,'paystackpaymentprovider'=>new ShopPaymentProvider()];$core->init();$core->objects['paystackpaymentprovider']->intents=$intents;
$assert=function($c,$m){if(!$c)throw new RuntimeException($m);};
$input=['userId'=>null,'purposeType'=>'shop_order','purposeId'=>str_repeat('b',32),'productCode'=>'shop-order-fixture','priceVersion'=>'v1','amountMinor'=>23000,'currency'=>'ZAR','provider'=>'paystack','idempotencyKey'=>'shop-order:'.str_repeat('b',32),'correlationId'=>'shop-order:'.str_repeat('b',32)];
$assert(!$core->createIntent(array_merge($input,['amountMinor'=>1]))['ok'],'Tampered total rejected');
$assert(!$core->createIntent(array_merge($input,['provider'=>'fake']))['ok'],'Fake provider cannot be substituted');
$r=$core->createIntent($input);$assert($r['ok'],'Canonical shop order accepted');$assert($core->createIntent($input)['intentId']===$r['intentId'],'Intent is idempotent');
$started=$core->startCheckout($r['intentId']);$assert($started['ok'],'Checkout uses canonical shop order snapshot');
$core->recordBrowserReturn($r['intentId']);$assert($fulfil->issued===0,'Browser return grants nothing');
$event=['providerEventId'=>'event-success','intentId'=>$r['intentId'],'providerPaymentId'=>'fixture-payment','type'=>'payment.succeeded','occurredAt'=>'2026-10-04 12:00:00','reasonCode'=>null];
$assert(!$core->receiveProviderEvent('paystack',['event'=>$event])['ok']&&$fulfil->issued===0,'Unverified webhook denied');
$core->receiveProviderEvent('paystack',['verified'=>true,'event'=>$event]);$assert($fulfil->issued===1,'Verified success dispatched to shop');
$event['providerEventId']='event-dispute';$event['type']='payment.disputed';$core->receiveProviderEvent('paystack',['verified'=>true,'event'=>$event]);$assert($fulfil->revoked===1,'Dispute revokes fulfilment');
$event['providerEventId']='event-refund';$event['type']='payment.refunded';$core->receiveProviderEvent('paystack',['verified'=>true,'event'=>$event]);$assert($fulfil->revoked===2,'Refund revokes fulfilment');
$core->receiveProviderEvent('paystack',['verified'=>true,'event'=>$event]);$assert($fulfil->revoked===3,'Duplicate reversal can repair fulfilment');
$event['providerEventId']='late-success';$event['type']='payment.succeeded';$core->receiveProviderEvent('paystack',['verified'=>true,'event'=>$event]);$assert($core->intent($r['intentId'])['state']==='refunded','Late success cannot downgrade refund');
echo "PASS: canonical shop totals, guest identity, idempotent intents, browser-return denial, verified fulfilment, dispute/refund revocation and duplicate reversal recovery.\n";
