<?php
/** Yoco hosted-checkout adapter. Browser returns never confirm payment. */
if (empty($GLOBALS['kewl_entry_point_run'])) { die('You cannot view this page directly'); }

class yocopaymentprovider extends ChisimbaObject
{
    private const CHECKOUT_URL = 'https://payments.yoco.com/api/checkouts';
    private const SANDBOX_API_URL = 'https://api.yocosandbox.com';
    private const LIVE_API_URL = 'https://api.yoco.com';
    private const MAX_WEBHOOK_AGE = 180;

    public function init()
    {
        $this->config = $this->getObject('dbsysconfig', 'sysconfig');
        $this->intents = $this->getObject('dbpaymentintents');
    }

    public function code() { return 'yoco'; }
    public function isAvailable()
    {
        $mode=strtolower(trim((string)$this->config->getValue('PAYMENT_YOCO_MODE','payment-service')));
        return in_array($mode,array('sandbox','live'),true)
            && trim((string)$this->config->getValue('PAYMENT_YOCO_CHECKOUT_SECRET_KEY','payment-service'))!=='';
    }

    public function createCheckout(array $intent, $options=array())
    {
        if (!$this->isAvailable()) { return $this->failure('yoco_unavailable'); }
        if (!function_exists('curl_init')) { return $this->failure('curl_unavailable'); }
        $options=is_array($options)?$options:array();
        $payload=$this->buildCheckoutPayload($intent,$options);
        if ($payload===NULL) { return $this->failure('invalid_checkout_urls'); }
        $json=json_encode($payload,JSON_UNESCAPED_SLASHES);
        if ($json===false) { return $this->failure('json_encoding_failed'); }
        $secret=trim((string)$this->config->getValue('PAYMENT_YOCO_CHECKOUT_SECRET_KEY','payment-service'));
        $handle=curl_init(self::CHECKOUT_URL);
        curl_setopt_array($handle,array(
            CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>$json,CURLOPT_RETURNTRANSFER=>true,
            CURLOPT_CONNECTTIMEOUT=>10,CURLOPT_TIMEOUT=>30,
            CURLOPT_HTTPHEADER=>array('Authorization: Bearer '.$secret,'Content-Type: application/json','Idempotency-Key: '.$intent['idempotency_key']),
        ));
        $response=curl_exec($handle); $error=curl_error($handle);
        $status=(int)curl_getinfo($handle,CURLINFO_RESPONSE_CODE); curl_close($handle);
        if ($response===false) { return $this->failure('yoco_transport_error',true,$error); }
        $data=json_decode((string)$response,true);
        if ($status<200||$status>=300||!is_array($data)) {
            $providerCode=is_array($data)?($data['code']??$data['errorCode']??''):'';
            $providerCode=preg_replace('/[^A-Za-z0-9_-]/','_',is_scalar($providerCode)?(string)$providerCode:'');
            return $this->failure('yoco_checkout_rejected_'.$status.($providerCode!==''?'_'.$providerCode:''),$status>=500||$status===429);
        }
        $configuredMode=strtolower(trim((string)$this->config->getValue('PAYMENT_YOCO_MODE','payment-service')));
        $processingMode=strtolower(trim((string)($data['processingMode']??'')));
        if (($configuredMode==='sandbox'&&$processingMode!=='test')||($configuredMode==='live'&&$processingMode!=='live')) return $this->failure('yoco_mode_mismatch');
        $id=trim((string)($data['id']??'')); $url=trim((string)($data['redirectUrl']??''));
        if ($id===''||!filter_var($url,FILTER_VALIDATE_URL)||parse_url($url,PHP_URL_SCHEME)!=='https') return $this->failure('invalid_yoco_checkout_response');
        return array('ok'=>true,'code'=>'approval_required','providerReference'=>$id,'approvalUrl'=>$url);
    }

    public function buildCheckoutPayload(array $intent,array $options)
    {
        foreach(array('successUrl','cancelUrl','failureUrl') as $key) {
            if (!isset($options[$key])||!filter_var($options[$key],FILTER_VALIDATE_URL)||parse_url($options[$key],PHP_URL_SCHEME)!=='https') return NULL;
        }
        $payload=array(
            'amount'=>(int)$intent['amount_minor'],'currency'=>(string)$intent['currency'],
            'clientReferenceId'=>(string)$intent['correlation_id'],'externalId'=>(string)$intent['id'],
            'metadata'=>array('intentId'=>(string)$intent['id'],'productCode'=>(string)$intent['product_code']),
        );
        // Yoco requires publicly resolvable return URLs; local .test hosts are intentionally omitted.
        $host=strtolower((string)parse_url($options['successUrl'],PHP_URL_HOST));
        if($host!==''&&!str_ends_with($host,'.test')&&!in_array($host,array('localhost','127.0.0.1','::1'),true)) {
            $payload['successUrl']=$options['successUrl']; $payload['cancelUrl']=$options['cancelUrl']; $payload['failureUrl']=$options['failureUrl'];
        }
        return $payload;
    }

    public function verifyAndNormalize(array $envelope)
    {
        $raw=is_scalar($envelope['rawBody']??NULL)?(string)$envelope['rawBody']:'';
        $headers=is_array($envelope['headers']??NULL)?array_change_key_case($envelope['headers'],CASE_LOWER):array();
        $id=trim((string)($headers['webhook-id']??'')); $timestamp=trim((string)($headers['webhook-timestamp']??''));
        $signature=trim((string)($headers['webhook-signature']??''));
        $secret=trim((string)$this->config->getValue('PAYMENT_YOCO_WEBHOOK_SECRET','payment-service'));
        if($raw===''||$id===''||!ctype_digit($timestamp)||$signature===''||!str_starts_with($secret,'whsec_')) return $this->failure('invalid_webhook_envelope');
        if(abs(time()-(int)$timestamp)>self::MAX_WEBHOOK_AGE) return $this->failure('stale_webhook');
        $secretBytes=base64_decode(substr($secret,6),true); if($secretBytes===false) return $this->failure('invalid_webhook_secret');
        $expected=base64_encode(hash_hmac('sha256',$id.'.'.$timestamp.'.'.$raw,$secretBytes,true));
        $matched=false; foreach(preg_split('/\s+/',trim($signature)) as $candidate){$parts=explode(',',$candidate,2); if(count($parts)===2&&$parts[0]==='v1'&&hash_equals($expected,$parts[1])){$matched=true;break;}}
        if(!$matched) return $this->failure('invalid_webhook_signature');
        $body=json_decode($raw,true);
        if(!is_array($body)) return $this->failure('invalid_webhook_json');
        // Hosted Checkout and the newer Yoco API use the same signature scheme,
        // but deliberately different event envelopes. Chisimba creates Hosted
        // Checkout sessions, so that envelope is the primary path. Retain the
        // Yoco API envelope as an additive compatibility path for installations
        // that have separately provisioned access to that API.
        if(isset($body['type'])) return $this->normalizeCheckoutEvent($body,$id,$timestamp);
        return $this->normalizeApiEvent($body,$id,$timestamp);
    }

    private function normalizeCheckoutEvent(array $body,$webhookId,$timestamp)
    {
        $payload=is_array($body['payload']??NULL)?$body['payload']:array();
        $checkoutId=trim((string)($payload['metadata']['checkoutId']??''));
        $intent=$checkoutId===''?NULL:$this->intents->byProviderReference($checkoutId);
        if($intent===NULL) return array('ok'=>true,'ignored'=>true,'code'=>'unrelated_yoco_checkout');
        $rawType=trim((string)($body['type']??''));
        $amount=(int)($payload['amount']??-1);
        $currency=strtoupper(trim((string)($payload['currency']??'')));
        $validAmount=$rawType==='refund.succeeded'
            ?($amount>0&&$amount<=(int)$intent['amount_minor'])
            :($amount===(int)$intent['amount_minor']);
        if(!$validAmount||$currency!==(string)$intent['currency']) return $this->failure('payment_reconciliation_mismatch');
        $mode=strtolower(trim((string)($payload['mode']??'')));
        $configuredMode=strtolower(trim((string)$this->config->getValue('PAYMENT_YOCO_MODE','payment-service')));
        if(($configuredMode==='sandbox'&&$mode!=='test')||($configuredMode==='live'&&$mode!=='live')) return $this->failure('yoco_mode_mismatch');
        try{$occurred=(new DateTimeImmutable((string)($body['createdDate']??('@'.$timestamp))))->format('Y-m-d H:i:s');}catch(Throwable $failure){return $this->failure('invalid_event_time');}
        $status=strtolower(trim((string)($payload['status']??'')));
        if($rawType==='payment.succeeded'&&$status==='succeeded') $type='payment.succeeded';
        elseif($rawType==='payment.failed'&&$status==='failed') $type='payment.failed';
        elseif($rawType==='refund.succeeded'&&$status==='succeeded'&&!array_key_exists('refundableAmount',$payload)) $type='payment.refunded';
        else return array('ok'=>true,'ignored'=>true,'code'=>$rawType==='refund.succeeded'?'partial_refund_requires_review':'unsupported_yoco_event','ignoredEvent'=>array(
            'providerEventId'=>(string)($body['id']??$webhookId),'intentId'=>$intent['id'],'type'=>$rawType?:'unknown',
            'reasonCode'=>isset($payload['failureReason'])?substr(preg_replace('/[^A-Za-z0-9_.:-]/','_',strtolower((string)$payload['failureReason'])),0,96):NULL,
            'occurredAt'=>$occurred,
        ));
        return array('ok'=>true,'event'=>array(
            'providerEventId'=>(string)($body['id']??$webhookId),'intentId'=>$intent['id'],
            'providerPaymentId'=>(string)($payload['id']??$checkoutId),'type'=>$type,
            'reasonCode'=>$type==='payment.failed'?(isset($payload['failureReason'])?substr(preg_replace('/[^A-Za-z0-9_.:-]/','_',strtolower((string)$payload['failureReason'])),0,96):'failed'):NULL,
            'occurredAt'=>$occurred,
        ));
    }

    private function normalizeApiEvent(array $body,$id,$timestamp)
    {
        $rawType=trim((string)($body['event_type']??''));
        $paymentId=trim((string)($body['payment_id']??''));
        if(!in_array($rawType,array('payment.created','payment.refunded'),true)||!preg_match('/^[A-Za-z0-9:-]{1,100}$/',$paymentId)) return $this->failure('unsupported_yoco_event');
        $lookup=$this->fetchPayment($paymentId);
        if(empty($lookup['ok'])||!is_array($lookup['payment']??NULL)) return $lookup;
        $payment=$lookup['payment'];
        $checkoutId=trim((string)($payment['checkout_id']??''));
        $externalId=strtolower(trim((string)($payment['external_id']??'')));
        $intent=$checkoutId===''?NULL:$this->intents->byProviderReference($checkoutId);
        if($intent===NULL&&preg_match('/^[a-f0-9]{32}$/',$externalId)) $intent=$this->intents->byId($externalId);
        if($intent===NULL) return array('ok'=>true,'ignored'=>true,'code'=>'unrelated_yoco_checkout');
        $amount=(int)($payment['total_amount']['amount']??-1);
        $currency=strtoupper(trim((string)($payment['total_amount']['currency']??$payment['currency']??'')));
        if($checkoutId===''||$externalId!==strtolower((string)$intent['id'])||$amount!==(int)$intent['amount_minor']||$currency!==(string)$intent['currency']) return $this->failure('payment_reconciliation_mismatch');
        try{$occurred=(new DateTimeImmutable((string)($payment['updated_at']??$payment['created_at']??('@'.$timestamp))))->format('Y-m-d H:i:s');}catch(Throwable $failure){return $this->failure('invalid_event_time');}
        $status=strtolower(trim((string)($payment['status']??'')));
        if($rawType==='payment.created') {
            if($status==='approved') $type='payment.succeeded';
            elseif(in_array($status,array('failed','cancelled'),true)) $type='payment.failed';
            else return $this->failure('payment_not_final',true);
        } else {
            $refunded=(int)($payment['refunded_amount']['amount']??-1);
            if($refunded<0||$refunded>$amount) return $this->failure('invalid_refund_amount');
            if($refunded<$amount) return array('ok'=>true,'ignored'=>true,'code'=>'partial_refund_requires_review','ignoredEvent'=>array(
                'providerEventId'=>$id,'intentId'=>$intent['id'],'type'=>'payment.refunded',
                'reasonCode'=>'partial_refund','occurredAt'=>$occurred,
            ));
            $type='payment.refunded';
        }
        return array('ok'=>true,'event'=>array(
            'providerEventId'=>$id,'intentId'=>$intent['id'],
            'providerPaymentId'=>$paymentId,'type'=>$type,
            'reasonCode'=>$type==='payment.failed'?$status:NULL,'occurredAt'=>$occurred,
        ));
    }

    protected function fetchPayment($paymentId)
    {
        if(!function_exists('curl_init')) return $this->failure('curl_unavailable',true);
        $mode=strtolower(trim((string)$this->config->getValue('PAYMENT_YOCO_MODE','payment-service')));
        $base=$mode==='sandbox'?self::SANDBOX_API_URL:self::LIVE_API_URL;
        $secret=trim((string)$this->config->getValue('PAYMENT_YOCO_CHECKOUT_SECRET_KEY','payment-service'));
        $handle=curl_init($base.'/v1/payments/'.rawurlencode($paymentId));
        curl_setopt_array($handle,array(
            CURLOPT_RETURNTRANSFER=>true,CURLOPT_CONNECTTIMEOUT=>10,CURLOPT_TIMEOUT=>30,
            CURLOPT_HTTPHEADER=>array('Authorization: Bearer '.$secret,'Accept: application/json'),
        ));
        $response=curl_exec($handle); $error=curl_error($handle);
        $status=(int)curl_getinfo($handle,CURLINFO_RESPONSE_CODE); curl_close($handle);
        if($response===false) return $this->failure('yoco_payment_lookup_transport',true,$error);
        $payment=json_decode((string)$response,true);
        if($status<200||$status>=300||!is_array($payment)) return $this->failure('yoco_payment_lookup_rejected_'.$status,$status>=500||$status===429);
        return array('ok'=>true,'payment'=>$payment);
    }

    private function failure($code,$retryable=false,$detail=NULL){return array('ok'=>false,'code'=>$code,'retryable'=>(bool)$retryable,'detail'=>$detail);}
}
?>
