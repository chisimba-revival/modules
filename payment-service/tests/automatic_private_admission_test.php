<?php
$GLOBALS['kewl_entry_point_run']=true;
class ChisimbaObject {
    public $objects=array();
    public function getObject($name,$module=null){return $this->objects[$name];}
}
class IntentStore {
    public $row;
    public function __construct($row){$this->row=$row;}
    public function byId($id){return $id===$this->row['id']?$this->row:null;}
    public function transition($id,$from,$fields){if($this->row['state']!==$from)return false;$this->row=array_merge($this->row,$fields);return true;}
}
class EventStore { public $duplicate=false; public function claim($v){return array('ok'=>true,'duplicate'=>$this->duplicate,'id'=>'event');} public function complete($id,$code){return true;} }
class PaymentStore { public $rows=array(); public function record($v){$this->rows[]=$v;return true;} }
class UserStore { public function findByUserId($id){return array('userid'=>$id);} }
class AuditStore { public $events=array(); public function append($v){$this->events[]=$v;return array('ok'=>true);} }
class ProviderStub { public function isAvailable(){return true;} public function verifyAndNormalize($e){return array('ok'=>true,'event'=>$e['event']);} }
class AdmissionSpy { public $calls=array(); public function admitConfirmedPayment($course,$user,$reference,$correlation){$this->calls[]=func_get_args();return array('ok'=>true,'code'=>'admitted');} }

require dirname(__DIR__).'/classes/paymentservice_class_inc.php';
$id=str_repeat('a',32);
$intent=array('id'=>$id,'user_id'=>'user-1','purpose_type'=>'private_course','purpose_id'=>'course-1','product_code'=>'course','price_version'=>'v1','amount_minor'=>1000,'currency'=>'ZAR','provider_code'=>'fake','state'=>'awaiting_approval','correlation_id'=>'payment:test');
$service=new paymentservice();
$intents=new IntentStore($intent);$events=new EventStore();$payments=new PaymentStore();$audits=new AuditStore();$provider=new ProviderStub();$admissions=new AdmissionSpy();
$service->objects=array('dbpaymentintents'=>$intents,'dbpaymentevents'=>$events,'dbpayments'=>$payments,'userservice'=>new UserStore(),'accounteventservice'=>$audits,'fakepaymentprovider'=>$provider,'privateadmissionservice'=>$admissions);
$service->init();
$expect=function($ok,$message){if(!$ok)throw new RuntimeException($message);};
$service->recordBrowserReturn($id,'browser-reference');
$expect(count($admissions->calls)===0,'Browser return must never admit.');
$event=array('providerEventId'=>'event-1','intentId'=>$id,'providerPaymentId'=>'payment-1','type'=>'payment.succeeded','reasonCode'=>null,'occurredAt'=>'2026-08-25 12:00:00');
$result=$service->receiveProviderEvent('fake',array('event'=>$event));
$expect($result['code']==='payment_succeeded','Verified success should complete payment.');
$expect(count($admissions->calls)===1&&$admissions->calls[0][0]==='course-1','Verified success must request automatic course admission.');
$events->duplicate=true;
$result=$service->receiveProviderEvent('fake',array('event'=>$event));
$expect($result['code']==='duplicate_event_ignored'&&count($admissions->calls)===2,'Duplicate success must safely retry admission reconciliation.');
$events->duplicate=false;$intents->row['state']='awaiting_approval';$event['providerEventId']='event-2';$event['type']='payment.failed';$event['reasonCode']='card_declined';
$service->receiveProviderEvent('fake',array('event'=>$event));
$expect(count($admissions->calls)===2,'Failed payment must not request admission.');
echo "PASS: verified automatic private-admission bridge\n";
