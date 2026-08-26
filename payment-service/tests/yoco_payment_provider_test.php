<?php
$GLOBALS['kewl_entry_point_run']=true;
class YocoProviderBase { public $config; public $intents; }
class YocoConfigStub {
    public $values=array();
    public function getValue($name,$module){return $this->values[$name]??'';}
}
class YocoIntentStub {
    public $intent;
    public function byProviderReference($reference){return $reference==='ch_test_123'?$this->intent:null;}
    public function byId($id){return $id===($this->intent['id']??null)?$this->intent:null;}
}
$source=file_get_contents(dirname(__DIR__).'/classes/yocopaymentprovider_class_inc.php');
$source=preg_replace('/^<\?php|\?>\s*$/','',$source);
$source=str_replace('class yocopaymentprovider extends ChisimbaObject','class yocopaymentprovider extends YocoProviderBase',$source);
eval($source);
class TestYocoProvider extends yocopaymentprovider {
    public $payment;
    protected function fetchPayment($paymentId){return array('ok'=>true,'payment'=>$this->payment);}
}
$expect=function($condition,$message){if(!$condition)throw new RuntimeException($message);};
$provider=new TestYocoProvider(); $provider->config=new YocoConfigStub(); $provider->intents=new YocoIntentStub();
$provider->config->values=array(
    'PAYMENT_YOCO_MODE'=>'sandbox','PAYMENT_YOCO_CHECKOUT_SECRET_KEY'=>'sk_test_contract',
    'PAYMENT_YOCO_WEBHOOK_SECRET'=>'whsec_'.base64_encode('contract-secret'),
);
$intent=array('id'=>str_repeat('a',32),'idempotency_key'=>'checkout:test','amount_minor'=>12500,'currency'=>'ZAR','correlation_id'=>'checkout:test','product_code'=>'tier-1');
$provider->intents->intent=$intent;
$payload=$provider->buildCheckoutPayload($intent,array(
    'successUrl'=>'https://example.test/success','cancelUrl'=>'https://example.test/cancel','failureUrl'=>'https://example.test/failure',
));
$expect($payload['amount']===12500&&$payload['currency']==='ZAR','Checkout must use the canonical server-side amount.');
$expect($payload['externalId']===$intent['id'],'Checkout must carry the internal intent reference.');
$expect($provider->buildCheckoutPayload($intent,array('successUrl'=>'http://example.test','cancelUrl'=>'https://example.test','failureUrl'=>'https://example.test'))===null,'Return URLs must use HTTPS.');
$provider->payment=array(
    'id'=>'pay-test-1','checkout_id'=>'ch_test_123','external_id'=>$intent['id'],
    'status'=>'approved','created_at'=>gmdate('c'),'updated_at'=>gmdate('c'),
    'total_amount'=>array('amount'=>12500,'currency'=>'ZAR'),
    'refunded_amount'=>array('amount'=>0,'currency'=>'ZAR'),
);
$body=json_encode(array('event_type'=>'payment.created','payment_id'=>'pay-test-1','business_id'=>'business_1','order_id'=>'order_1'),JSON_UNESCAPED_SLASHES);
$timestamp=(string)time(); $webhookId='msg_test_1';
$signature=base64_encode(hash_hmac('sha256',$webhookId.'.'.$timestamp.'.'.$body,'contract-secret',true));
$envelope=array('rawBody'=>$body,'headers'=>array('webhook-id'=>$webhookId,'webhook-timestamp'=>$timestamp,'webhook-signature'=>'v1,'.$signature));
$verified=$provider->verifyAndNormalize($envelope);
$expect(!empty($verified['ok'])&&$verified['event']['type']==='payment.succeeded','A current correctly signed payment must verify.');
$tampered=$envelope; $tampered['rawBody'].=' ';
$expect(empty($provider->verifyAndNormalize($tampered)['ok']),'A modified raw body must fail verification.');
$stale=$envelope; $stale['headers']['webhook-timestamp']=(string)(time()-181);
$stale['headers']['webhook-signature']='v1,'.base64_encode(hash_hmac('sha256',$webhookId.'.'.$stale['headers']['webhook-timestamp'].'.'.$body,'contract-secret',true));
$expect(($provider->verifyAndNormalize($stale)['code']??'')==='stale_webhook','A webhook outside the replay window must fail.');
$provider->payment['refunded_amount']['amount']=500;
$partial=array('event_type'=>'payment.refunded','payment_id'=>'pay-test-1','business_id'=>'business_1','order_id'=>'order_1');
$partialBody=json_encode($partial,JSON_UNESCAPED_SLASHES); $partialSig=base64_encode(hash_hmac('sha256',$webhookId.'.'.$timestamp.'.'.$partialBody,'contract-secret',true));
$partialResult=$provider->verifyAndNormalize(array('rawBody'=>$partialBody,'headers'=>array('webhook-id'=>$webhookId,'webhook-timestamp'=>$timestamp,'webhook-signature'=>'v1,'.$partialSig)));
$expect(!empty($partialResult['ok'])&&!empty($partialResult['ignored'])&&$partialResult['code']==='partial_refund_requires_review'&&!empty($partialResult['ignoredEvent']),'A partial refund must be auditable without revoking all access automatically.');
$provider->payment['refunded_amount']['amount']=12500;
$fullBody=json_encode($partial,JSON_UNESCAPED_SLASHES); $fullSig=base64_encode(hash_hmac('sha256',$webhookId.'.'.$timestamp.'.'.$fullBody,'contract-secret',true));
$fullResult=$provider->verifyAndNormalize(array('rawBody'=>$fullBody,'headers'=>array('webhook-id'=>$webhookId,'webhook-timestamp'=>$timestamp,'webhook-signature'=>'v1,'.$fullSig)));
$expect(($fullResult['event']['type']??'')==='payment.refunded','A full refund must reverse the fulfilled payment.');
$provider->payment['refunded_amount']['amount']=0; $provider->payment['total_amount']['amount']=12499;
$mismatch=$provider->verifyAndNormalize($envelope);
$expect(($mismatch['code']??'')==='payment_reconciliation_mismatch','A mismatched server-side amount must never fulfil access.');
fwrite(STDOUT,"PASS: Yoco hosted checkout and webhook contract\n");
?>
