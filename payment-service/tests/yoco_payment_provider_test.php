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
$timestamp=(string)time(); $webhookId='msg_test_1';
$checkoutBody=json_encode(array(
    'createdDate'=>gmdate('c'),'id'=>'evt_checkout_success','type'=>'payment.succeeded',
    'payload'=>array(
        'amount'=>12500,'currency'=>'ZAR','id'=>'pay-checkout-1','mode'=>'test',
        'status'=>'succeeded','type'=>'payment','metadata'=>array('checkoutId'=>'ch_test_123'),
    ),
),JSON_UNESCAPED_SLASHES);
$checkoutSignature=base64_encode(hash_hmac('sha256',$webhookId.'.'.$timestamp.'.'.$checkoutBody,'contract-secret',true));
$checkoutResult=$provider->verifyAndNormalize(array('rawBody'=>$checkoutBody,'headers'=>array(
    'webhook-id'=>$webhookId,'webhook-timestamp'=>$timestamp,'webhook-signature'=>'v1,'.$checkoutSignature,
)));
$expect(($checkoutResult['event']['type']??'')==='payment.succeeded','The Hosted Checkout payment event must be the canonical Yoco success path.');
$checkoutFailed=json_decode($checkoutBody,true); $checkoutFailed['id']='evt_checkout_failed'; $checkoutFailed['type']='payment.failed';
$checkoutFailed['payload']['status']='failed'; $checkoutFailed['payload']['failureReason']='card_declined';
$checkoutFailedBody=json_encode($checkoutFailed,JSON_UNESCAPED_SLASHES);
$checkoutFailedSignature=base64_encode(hash_hmac('sha256',$webhookId.'.'.$timestamp.'.'.$checkoutFailedBody,'contract-secret',true));
$checkoutFailedResult=$provider->verifyAndNormalize(array('rawBody'=>$checkoutFailedBody,'headers'=>array(
    'webhook-id'=>$webhookId,'webhook-timestamp'=>$timestamp,'webhook-signature'=>'v1,'.$checkoutFailedSignature,
)));
$expect(($checkoutFailedResult['event']['type']??'')==='payment.failed'&&($checkoutFailedResult['event']['reasonCode']??'')==='card_declined','A failed Hosted Checkout payment must be recorded without granting access.');
$checkoutPartial=json_decode($checkoutBody,true); $checkoutPartial['id']='evt_checkout_partial'; $checkoutPartial['type']='refund.succeeded';
$checkoutPartial['payload']['amount']=500; $checkoutPartial['payload']['id']='refund-partial-1'; $checkoutPartial['payload']['type']='refund'; $checkoutPartial['payload']['refundableAmount']=12000;
$checkoutPartialBody=json_encode($checkoutPartial,JSON_UNESCAPED_SLASHES);
$checkoutPartialSignature=base64_encode(hash_hmac('sha256',$webhookId.'.'.$timestamp.'.'.$checkoutPartialBody,'contract-secret',true));
$checkoutPartialResult=$provider->verifyAndNormalize(array('rawBody'=>$checkoutPartialBody,'headers'=>array(
    'webhook-id'=>$webhookId,'webhook-timestamp'=>$timestamp,'webhook-signature'=>'v1,'.$checkoutPartialSignature,
)));
$expect(($checkoutPartialResult['code']??'')==='partial_refund_requires_review','A Hosted Checkout partial refund must be retained for review.');
$checkoutFull=$checkoutPartial; $checkoutFull['payload']['amount']=12500; unset($checkoutFull['payload']['refundableAmount']); $checkoutFull['id']='evt_checkout_full';
$checkoutFullBody=json_encode($checkoutFull,JSON_UNESCAPED_SLASHES);
$checkoutFullSignature=base64_encode(hash_hmac('sha256',$webhookId.'.'.$timestamp.'.'.$checkoutFullBody,'contract-secret',true));
$checkoutFullResult=$provider->verifyAndNormalize(array('rawBody'=>$checkoutFullBody,'headers'=>array(
    'webhook-id'=>$webhookId,'webhook-timestamp'=>$timestamp,'webhook-signature'=>'v1,'.$checkoutFullSignature,
)));
$expect(($checkoutFullResult['event']['type']??'')==='payment.refunded','A Hosted Checkout full refund must reverse fulfilled access.');
$envelope=array('rawBody'=>$checkoutBody,'headers'=>array('webhook-id'=>$webhookId,'webhook-timestamp'=>$timestamp,'webhook-signature'=>'v1,'.$checkoutSignature));
$tampered=$envelope; $tampered['rawBody'].=' ';
$expect(empty($provider->verifyAndNormalize($tampered)['ok']),'A modified raw body must fail verification.');
$stale=$envelope; $stale['headers']['webhook-timestamp']=(string)(time()-181);
$stale['headers']['webhook-signature']='v1,'.base64_encode(hash_hmac('sha256',$webhookId.'.'.$stale['headers']['webhook-timestamp'].'.'.$checkoutBody,'contract-secret',true));
$expect(($provider->verifyAndNormalize($stale)['code']??'')==='stale_webhook','A webhook outside the replay window must fail.');
$mismatch=json_decode($checkoutBody,true); $mismatch['payload']['amount']=12499;
$mismatchBody=json_encode($mismatch,JSON_UNESCAPED_SLASHES); $mismatchSignature=base64_encode(hash_hmac('sha256',$webhookId.'.'.$timestamp.'.'.$mismatchBody,'contract-secret',true));
$mismatchResult=$provider->verifyAndNormalize(array('rawBody'=>$mismatchBody,'headers'=>array('webhook-id'=>$webhookId,'webhook-timestamp'=>$timestamp,'webhook-signature'=>'v1,'.$mismatchSignature)));
$expect(($mismatchResult['code']??'')==='payment_reconciliation_mismatch','A mismatched signed amount must never fulfil access.');
fwrite(STDOUT,"PASS: Yoco hosted checkout and webhook contract\n");
?>
