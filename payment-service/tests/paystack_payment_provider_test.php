<?php
class PaystackProviderBase {}
class PaystackConfigStub {public $values=array();public function getValue($key,$module){return $this->values[$key]??'';}}
class PaystackIntentStub {public $rows=array();public function byProviderReference($reference){return $this->rows[$reference]??null;}public function byId($id){foreach($this->rows as $row)if($row['id']===$id)return $row;return null;}}
class PaystackPlanStub {}
class PaystackSubscriptionStub {public $mapping=null;public function byCustomerPlan($provider,$customer,$plan){return $this->mapping;}}
$source=file_get_contents(dirname(__DIR__).'/classes/paystackpaymentprovider_class_inc.php');
$source=preg_replace('/^<\?php/','',$source);$source=preg_replace('/\?>\s*$/','',$source);
$source=str_replace("if (empty(\$GLOBALS['kewl_entry_point_run'])) { die('You cannot view this page directly'); }",'',$source);
$source=str_replace('class paystackpaymentprovider extends ChisimbaObject','class paystackpaymentprovider extends PaystackProviderBase',$source);
eval($source);
$expect=function($condition,$message){if(!$condition)throw new RuntimeException($message);};
$provider=new paystackpaymentprovider();$provider->config=new PaystackConfigStub();$provider->intents=new PaystackIntentStub();$provider->plans=new PaystackPlanStub();$provider->subscriptions=new PaystackSubscriptionStub();
$provider->config->values=array('PAYMENT_PAYSTACK_MODE'=>'test','PAYMENT_PAYSTACK_SECRET_KEY'=>'sk_test_contract_secret');
$intent=array('id'=>str_repeat('a',32),'amount_minor'=>12500,'currency'=>'ZAR','product_code'=>'tier-1-monthly');
$reserved=$provider->createCheckout($intent,array('email'=>'student@demo.test','successUrl'=>'https://example.org/return'));
$expect(($reserved['code']??'')==='checkout_requires_deliverable_email','Reserved test email domains must be rejected before provider checkout.');
$provider->intents->rows[$intent['id']]=$intent;
$charge=array('event'=>'charge.success','data'=>array('id'=>991,'domain'=>'test','status'=>'success','reference'=>$intent['id'],'amount'=>12500,'currency'=>'ZAR','paid_at'=>'2026-08-27T09:00:00Z','customer'=>array('customer_code'=>'CUS_test'),'plan_object'=>array('plan_code'=>'PLN_test')));
$raw=json_encode($charge,JSON_UNESCAPED_SLASHES);$signature=hash_hmac('sha512',$raw,'sk_test_contract_secret');
$result=$provider->verifyAndNormalize(array('rawBody'=>$raw,'headers'=>array('x-paystack-signature'=>$signature)));
$expect(!empty($result['ok'])&&($result['event']['type']??'')==='payment.succeeded','A signed reconciled Paystack charge must normalize to canonical success.');
$expect(($result['event']['subscription']['providerPlanId']??'')==='PLN_test','A recurring charge must retain its Paystack plan mapping.');
$tampered=str_replace('12500','12501',$raw);$invalid=$provider->verifyAndNormalize(array('rawBody'=>$tampered,'headers'=>array('x-paystack-signature'=>$signature)));
$expect(empty($invalid['ok']),'A modified Paystack webhook body must fail signature verification.');
$wrongAmount=$charge;$wrongAmount['data']['amount']=12499;$wrongRaw=json_encode($wrongAmount,JSON_UNESCAPED_SLASHES);$wrongSig=hash_hmac('sha512',$wrongRaw,'sk_test_contract_secret');
$wrong=$provider->verifyAndNormalize(array('rawBody'=>$wrongRaw,'headers'=>array('x-paystack-signature'=>$wrongSig)));
$expect(($wrong['code']??'')==='payment_reconciliation_mismatch','A signed event with the wrong amount must not grant access.');
$provider->intents->rows[$intent['id']]=$intent;$partial=array('event'=>'refund.processed','data'=>array('transaction_reference'=>$intent['id'],'amount'=>'5000','currency'=>'ZAR','status'=>'processed'));$partialRaw=json_encode($partial,JSON_UNESCAPED_SLASHES);$partialSig=hash_hmac('sha512',$partialRaw,'sk_test_contract_secret');$partialResult=$provider->verifyAndNormalize(array('rawBody'=>$partialRaw,'headers'=>array('x-paystack-signature'=>$partialSig)));
$expect(!empty($partialResult['ignored'])&&($partialResult['code']??'')==='partial_refund_requires_review','A partial refund must be retained for review without revoking all access.');
$provider->subscriptions->mapping=array('base_intent_id'=>$intent['id']);$renewal=$charge;$renewal['data']['id']=992;$renewal['data']['reference']='renewal-ref-1';$renewalRaw=json_encode($renewal,JSON_UNESCAPED_SLASHES);$renewalSig=hash_hmac('sha512',$renewalRaw,'sk_test_contract_secret');
$renewed=$provider->verifyAndNormalize(array('rawBody'=>$renewalRaw,'headers'=>array('x-paystack-signature'=>$renewalSig)));
$expect(($renewed['event']['renewal']['baseIntentId']??'')===$intent['id'],'A later recurring charge must resolve to the canonical base membership purchase.');
fwrite(STDOUT,"PASS: Paystack hosted checkout, signature and recurring mapping contract\n");
?>
