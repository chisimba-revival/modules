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
}
$source=file_get_contents(dirname(__DIR__).'/classes/yocopaymentprovider_class_inc.php');
$source=preg_replace('/^<\?php|\?>\s*$/','',$source);
$source=str_replace('class yocopaymentprovider extends ChisimbaObject','class yocopaymentprovider extends YocoProviderBase',$source);
eval($source);
$expect=function($condition,$message){if(!$condition)throw new RuntimeException($message);};
$provider=new yocopaymentprovider(); $provider->config=new YocoConfigStub(); $provider->intents=new YocoIntentStub();
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
$body=json_encode(array(
    'createdDate'=>gmdate('c'),'id'=>'evt_test_1','type'=>'payment.succeeded',
    'payload'=>array('id'=>'pay_test_1','metadata'=>array('checkoutId'=>'ch_test_123')),
),JSON_UNESCAPED_SLASHES);
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
$partial=json_decode($body,true); $partial['id']='evt_test_2'; $partial['type']='refund.succeeded'; $partial['payload']['refundableAmount']=500;
$partialBody=json_encode($partial,JSON_UNESCAPED_SLASHES); $partialSig=base64_encode(hash_hmac('sha256',$webhookId.'.'.$timestamp.'.'.$partialBody,'contract-secret',true));
$partialResult=$provider->verifyAndNormalize(array('rawBody'=>$partialBody,'headers'=>array('webhook-id'=>$webhookId,'webhook-timestamp'=>$timestamp,'webhook-signature'=>'v1,'.$partialSig)));
$expect(!empty($partialResult['ok'])&&!empty($partialResult['ignored'])&&$partialResult['code']==='partial_refund_requires_review'&&!empty($partialResult['ignoredEvent']),'A partial refund must be auditable without revoking all access automatically.');
fwrite(STDOUT,"PASS: Yoco hosted checkout and webhook contract\n");
?>
