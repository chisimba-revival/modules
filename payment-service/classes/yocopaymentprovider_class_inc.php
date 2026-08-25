<?php
/** Yoco hosted-checkout adapter. Browser returns never confirm payment. */
if (empty($GLOBALS['kewl_entry_point_run'])) { die('You cannot view this page directly'); }

class yocopaymentprovider extends ChisimbaObject
{
    private const CHECKOUT_URL = 'https://payments.yoco.com/api/checkouts';
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
        $body=json_decode($raw,true); $payload=is_array($body['payload']??NULL)?$body['payload']:array();
        $checkoutId=trim((string)($payload['metadata']['checkoutId']??'')); $intent=$checkoutId===''?NULL:$this->intents->byProviderReference($checkoutId);
        if(!is_array($body)) return $this->failure('invalid_webhook_json');
        if($intent===NULL) return array('ok'=>true,'ignored'=>true,'code'=>'unrelated_yoco_checkout');
        $type=(string)($body['type']??'');
        try{$occurred=(new DateTimeImmutable((string)($body['createdDate']??'')))->format('Y-m-d H:i:s');}catch(Throwable $failure){return $this->failure('invalid_event_time');}
        if($type==='refund.succeeded'&&!array_key_exists('refundableAmount',$payload)) $type='payment.refunded';
        elseif($type==='payment.succeeded') $type='payment.succeeded';
        else return array('ok'=>true,'ignored'=>true,'code'=>$type==='refund.succeeded'?'partial_refund_requires_review':'unsupported_yoco_event','ignoredEvent'=>array(
            'providerEventId'=>(string)($body['id']??$id),'intentId'=>$intent['id'],'type'=>$type?:'unknown',
            'reasonCode'=>isset($payload['failureReason'])?substr(preg_replace('/[^A-Za-z0-9_.:-]/','_',strtolower((string)$payload['failureReason'])),0,96):NULL,
            'occurredAt'=>$occurred,
        ));
        return array('ok'=>true,'event'=>array(
            'providerEventId'=>(string)($body['id']??$id),'intentId'=>$intent['id'],
            'providerPaymentId'=>(string)($payload['id']??$checkoutId),'type'=>$type,
            'reasonCode'=>NULL,'occurredAt'=>$occurred,
        ));
    }

    private function failure($code,$retryable=false,$detail=NULL){return array('ok'=>false,'code'=>$code,'retryable'=>(bool)$retryable,'detail'=>$detail);}
}
?>
