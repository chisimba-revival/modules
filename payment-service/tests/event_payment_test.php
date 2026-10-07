<?php
/** Event dispatch contracts at the shared payment boundary; no network or mail.
 * @author Derek Keats <derek@dkeats.com>
 */
$GLOBALS['kewl_entry_point_run']=true;
class ChisimbaObject {public $objects=[];public function getObject($name,$module=''){return $this->objects[$name];}}
/** @author Derek Keats <derek@dkeats.com> */
class EventPaymentIntents {
    public $row;
    public function byId($id){return $this->row&&$this->row['id']===$id?$this->row:null;}
    public function byIdempotency($key){return $this->row&&$this->row['idempotency_key']===$key?$this->row:null;}
    public function create($r){$this->row=$r;return true;}
    public function transition($id,$state,$changes){if(!$this->row||$this->row['state']!==$state)return false;$this->row=array_merge($this->row,$changes);return true;}
}
/** @author Derek Keats <derek@dkeats.com> */
class EventPaymentClaims {
    public $keys=[];
    public function claim($v){$key=$v['provider_event_id'];$duplicate=isset($this->keys[$key]);$this->keys[$key]=true;return ['ok'=>true,'id'=>$key,'duplicate'=>$duplicate];}
    public function complete($id,$code){}
}
/** @author Derek Keats <derek@dkeats.com> */
class EventPaymentLedger {public function record($v){} public function successfulReferenceForIntent($id){return 'fixture-payment';}}
/** @author Derek Keats <derek@dkeats.com> */
class EventPaymentAudit {public function append($v){return ['ok'=>true];}}
/** @author Derek Keats <derek@dkeats.com> */
class EventPaymentUsers {public function findByUserId($id){throw new RuntimeException('Guests must not manufacture an account');}}
/** @author Derek Keats <derek@dkeats.com> */
class EventPaymentProvider {public function verifyAndNormalize($envelope){return empty($envelope['verified'])?['ok'=>false]:['ok'=>true,'event'=>$envelope['event']];}}
/** @author Derek Keats <derek@dkeats.com> */
class EventPaymentFulfilment {
    public $issued=0; public $revoked=0; public $core;
    public function matchesIntent($v){return $v['purpose_id']===str_repeat('b',32)&&$v['amount_minor']===23000&&$v['currency']==='ZAR'&&$v['user_id']===null&&$v['provider_code']==='yoco'&&$v['product_code']==='event-fixture'&&$v['price_version']==='v1'&&$v['idempotency_key']==='event:'.str_repeat('b',32);}
    public function fulfil($i){if($this->core->intent($i['id'])['state']!=='succeeded')return ['ok'=>false];$this->issued=1;return ['ok'=>true];}
    public function reverse($i){if(!in_array($this->core->intent($i['id'])['state'],['refunded','reversed','disputed'],true))return ['ok'=>false];$this->revoked++;return ['ok'=>true];}
}
require dirname(__DIR__).'/classes/paymentservice_class_inc.php';
$core=new paymentservice();$intents=new EventPaymentIntents();$fulfil=new EventPaymentFulfilment();$fulfil->core=$core;
$core->objects=['dbpaymentintents'=>$intents,'dbpaymentevents'=>new EventPaymentClaims(),'dbpayments'=>new EventPaymentLedger(),'dbpaymentsubscriptions'=>new stdClass(),'paymentcatalogservice'=>new stdClass(),'userservice'=>new EventPaymentUsers(),'accounteventservice'=>new EventPaymentAudit(),'eventservice'=>$fulfil,'yocopaymentprovider'=>new EventPaymentProvider()];$core->init();
$assert=function($c,$m){if(!$c)throw new RuntimeException($m);};
$input=['userId'=>null,'purposeType'=>'event','purposeId'=>str_repeat('b',32),'productCode'=>'event-fixture','priceVersion'=>'v1','amountMinor'=>23000,'currency'=>'ZAR','provider'=>'yoco','idempotencyKey'=>'event:'.str_repeat('b',32),'correlationId'=>'event:'.str_repeat('b',32)];
$assert(!$core->createIntent(array_merge($input,['amountMinor'=>1]))['ok'],'Tampered total rejected');
$assert(!$core->createIntent(array_merge($input,['provider'=>'fake']))['ok'],'Fake provider cannot be substituted');
$r=$core->createIntent($input);$assert($r['ok'],'Canonical event booking accepted');$assert($core->createIntent($input)['intentId']===$r['intentId'],'Intent is idempotent');
$core->recordBrowserReturn($r['intentId']);$assert($fulfil->issued===0,'Browser return grants nothing');
$event=['providerEventId'=>'event-success','intentId'=>$r['intentId'],'providerPaymentId'=>'fixture-payment','type'=>'payment.succeeded','occurredAt'=>'2026-10-04 12:00:00','reasonCode'=>null];
$assert(!$core->receiveProviderEvent('yoco',['event'=>$event])['ok']&&$fulfil->issued===0,'Unverified webhook denied');
$core->receiveProviderEvent('yoco',['verified'=>true,'event'=>$event]);$assert($fulfil->issued===1,'Verified success dispatched to events');
$event['providerEventId']='event-dispute';$event['type']='payment.disputed';$core->receiveProviderEvent('yoco',['verified'=>true,'event'=>$event]);$assert($fulfil->revoked===1,'Dispute revokes admission');
$event['providerEventId']='event-refund';$event['type']='payment.refunded';$core->receiveProviderEvent('yoco',['verified'=>true,'event'=>$event]);$assert($fulfil->revoked===2,'Refund revokes admission');
$core->receiveProviderEvent('yoco',['verified'=>true,'event'=>$event]);$assert($fulfil->revoked===3,'Duplicate reversal can repair fulfilment');
$event['providerEventId']='late-success';$event['type']='payment.succeeded';$core->receiveProviderEvent('yoco',['verified'=>true,'event'=>$event]);$assert($core->intent($r['intentId'])['state']==='refunded','Late success cannot downgrade refund');
echo "PASS: canonical event totals, guest identity, idempotent intents, browser-return denial, verified fulfilment, dispute/refund revocation and duplicate reversal recovery.\n";
