<?php
/** Paystack hosted-checkout and recurring-plan adapter. */
if (empty($GLOBALS['kewl_entry_point_run'])) { die('You cannot view this page directly'); }
class paystackpaymentprovider extends ChisimbaObject
{
    private const API='https://api.paystack.co';
    public function init(){
        $this->config=$this->getObject('dbsysconfig','sysconfig');
        $this->intents=$this->getObject('dbpaymentintents');
        $this->plans=$this->getObject('dbpaymentproviderplans');
        $this->subscriptions=$this->getObject('dbpaymentsubscriptions');
    }
    public function code(){return 'paystack';}
    public function isAvailable(){
        $mode=strtolower(trim((string)$this->config->getValue('PAYMENT_PAYSTACK_MODE','payment-service')));
        $secret=trim((string)$this->config->getValue('PAYMENT_PAYSTACK_SECRET_KEY','payment-service'));
        return in_array($mode,array('test','live'),true)&&str_starts_with($secret,$mode==='live'?'sk_live_':'sk_test_');
    }
    public function createCheckout(array $intent,$options=array()){
        if(!$this->isAvailable())return $this->failure('paystack_unavailable');
        $options=is_array($options)?$options:array();
        $email=filter_var($options['email']??'',FILTER_VALIDATE_EMAIL);
        $callback=$options['successUrl']??'';
        if($email===false||$this->reservedEmailDomain($email)||!$this->httpsUrl($callback))return $this->failure('checkout_requires_deliverable_email');
        $product=is_array($options['product']??null)?$options['product']:array();
        $payload=array(
            'email'=>$email,'amount'=>(int)$intent['amount_minor'],'currency'=>(string)$intent['currency'],
            'reference'=>(string)$intent['id'],'callback_url'=>$callback,
            'metadata'=>array('intentId'=>(string)$intent['id'],'productCode'=>(string)$intent['product_code'],'cancel_action'=>$options['cancelUrl']??$callback),
        );
        $period=(string)($product['billing_period']??'one_off');
        if(in_array($period,array('monthly','annual'),true)){
            $plan=$this->ensurePlan($intent,$product,$period);
            if(empty($plan['ok']))return $plan;
            $payload['plan']=$plan['providerPlanId'];
            // Paystack validates the required amount field, then the plan owns the billed amount.
        }
        $response=$this->request('POST','/transaction/initialize',$payload);
        if(empty($response['ok']))return $response;
        $data=$response['data']['data']??null;
        $reference=trim((string)($data['reference']??''));
        $url=trim((string)($data['authorization_url']??''));
        if($reference===''||!$this->httpsUrl($url))return $this->failure('invalid_paystack_checkout_response');
        return array('ok'=>true,'code'=>'approval_required','providerReference'=>$reference,'approvalUrl'=>$url);
    }
    private function ensurePlan(array $intent,array $product,$period){
        $mapping=$this->plans->mapping('paystack',$intent['product_code'],$intent['price_version']);
        if(is_array($mapping))return array('ok'=>true,'providerPlanId'=>$mapping['provider_plan_id']);
        $response=$this->request('POST','/plan',array('name'=>(string)($product['name']??$intent['product_code']),'amount'=>(int)$intent['amount_minor'],'interval'=>$period==='annual'?'annually':'monthly','currency'=>(string)$intent['currency']));
        if(empty($response['ok']))return $response;
        $code=trim((string)($response['data']['data']['plan_code']??''));
        if($code==='')return $this->failure('invalid_paystack_plan_response');
        $saved=$this->plans->remember(array('provider_code'=>'paystack','product_code'=>$intent['product_code'],'price_version'=>$intent['price_version'],'provider_plan_id'=>$code));
        return $saved===null?$this->failure('paystack_plan_mapping_failed',true):array('ok'=>true,'providerPlanId'=>$code);
    }
    public function verifyPayment(array $intent){
        $reference=trim((string)($intent['provider_reference']??''));
        if($reference==='')return $this->failure('missing_paystack_reference');
        $response=$this->request('GET','/transaction/verify/'.rawurlencode($reference));
        if(empty($response['ok']))return $response;
        return $this->normalizeCharge($response['data']['data']??array(),'verify:'.$reference);
    }
    public function verifyAndNormalize(array $envelope){
        $raw=is_scalar($envelope['rawBody']??null)?(string)$envelope['rawBody']:'';
        $headers=is_array($envelope['headers']??null)?array_change_key_case($envelope['headers'],CASE_LOWER):array();
        $signature=trim((string)($headers['x-paystack-signature']??''));
        $secret=$this->secret();
        if($raw===''||$signature===''||$secret==='')return $this->failure('invalid_webhook_envelope');
        $expected=hash_hmac('sha512',$raw,$secret);
        if(!hash_equals($expected,strtolower($signature)))return $this->failure('invalid_webhook_signature');
        $body=json_decode($raw,true);if(!is_array($body))return $this->failure('invalid_webhook_json');
        $type=trim((string)($body['event']??''));$data=is_array($body['data']??null)?$body['data']:array();
        $eventId='paystack:'.hash('sha256',$raw);
        if($type==='charge.success')return $this->normalizeCharge($data,$eventId);
        if($type==='invoice.payment_failed')return $this->normalizeInvoiceFailure($data,$eventId);
        if(in_array($type,array('subscription.create','subscription.not_renew','subscription.disable'),true)){
            $descriptor=$this->subscriptionDescriptor($data);
            if($descriptor===null)return array('ok'=>true,'ignored'=>true,'code'=>'unmapped_paystack_subscription');
            return array('ok'=>true,'subscriptionUpdate'=>array('descriptor'=>$descriptor,'state'=>$type==='subscription.create'?'active':($type==='subscription.not_renew'?'non_renewing':'disabled')));
        }
        if(in_array($type,array('refund.processed','charge.dispute.create','charge.dispute.remind'),true))return $this->normalizeAdverse($type,$data,$eventId);
        return array('ok'=>true,'ignored'=>true,'code'=>'unsupported_paystack_event');
    }
    private function normalizeCharge(array $data,$eventId){
        $reference=trim((string)($data['reference']??''));
        $metadata=is_array($data['metadata']??null)?$data['metadata']:array();
        $intent=$reference===''?null:$this->intents->byProviderReference($reference);
        if($intent===null&&!empty($metadata['intentId']))$intent=$this->intents->byId((string)$metadata['intentId']);
        $descriptor=$this->subscriptionDescriptor($data);
        if($descriptor!==null&&empty($descriptor['providerSubscriptionId'])) {
            $descriptor=$this->resolveSubscriptionDescriptor($data,$descriptor);
        }
        $renewal=null;
        if($intent===null&&$descriptor!==null){
            $mapping=$this->subscriptions->byCustomerPlan('paystack',$descriptor['providerCustomerId'],$descriptor['providerPlanId']);
            if(is_array($mapping))$renewal=array('baseIntentId'=>$mapping['base_intent_id'],'reference'=>$reference);
        }
        if($intent===null&&$renewal===null)return array('ok'=>true,'ignored'=>true,'code'=>'unrelated_paystack_charge');
        $amount=(int)($data['amount']??-1);$currency=strtoupper(trim((string)($data['currency']??'')));
        $expected=$intent?:$this->intents->byId($renewal['baseIntentId']);
        if(!is_array($expected)||$amount!==(int)$expected['amount_minor']||$currency!==(string)$expected['currency'])return $this->failure('payment_reconciliation_mismatch');
        $domain=strtolower(trim((string)($data['domain']??'')));$mode=strtolower(trim((string)$this->config->getValue('PAYMENT_PAYSTACK_MODE','payment-service')));
        if(($mode==='test'&&$domain!=='test')||($mode==='live'&&$domain!=='live'))return $this->failure('paystack_mode_mismatch');
        if(strtolower((string)($data['status']??''))!=='success')return $this->failure('paystack_charge_not_successful');
        $occurred=$this->time($data['paid_at']??$data['paidAt']??$data['created_at']??'now');
        return array('ok'=>true,'event'=>array('providerEventId'=>$eventId,'intentId'=>$intent['id']??'','providerPaymentId'=>(string)($data['id']??$reference),'type'=>'payment.succeeded','reasonCode'=>null,'occurredAt'=>$occurred,'renewal'=>$renewal,'subscription'=>$descriptor));
    }
    private function normalizeInvoiceFailure(array $data,$eventId){
        $descriptor=$this->subscriptionDescriptor($data);if($descriptor===null)return array('ok'=>true,'ignored'=>true,'code'=>'unmapped_paystack_invoice');
        $mapping=$this->subscriptions->byCustomerPlan('paystack',$descriptor['providerCustomerId'],$descriptor['providerPlanId']);
        if(!is_array($mapping))return array('ok'=>true,'ignored'=>true,'code'=>'unmapped_paystack_invoice');
        $reference=(string)($data['invoice_code']??$eventId);
        return array('ok'=>true,'event'=>array('providerEventId'=>$eventId,'intentId'=>'','providerPaymentId'=>$reference,'type'=>'payment.failed','reasonCode'=>'renewal_failed','occurredAt'=>$this->time($data['created_at']??'now'),'renewal'=>array('baseIntentId'=>$mapping['base_intent_id'],'reference'=>$reference),'subscription'=>$descriptor));
    }
    private function normalizeAdverse($rawType,array $data,$eventId){
        $transaction=is_array($data['transaction']??null)?$data['transaction']:$data;
        $reference=trim((string)($data['transaction_reference']??$transaction['reference']??''));$intent=$reference===''?null:$this->intents->byProviderReference($reference);
        if($intent===null)return array('ok'=>true,'ignored'=>true,'code'=>'unrelated_paystack_adverse_event');
        $type=$rawType==='refund.processed'?'payment.refunded':'payment.disputed';
        if($type==='payment.refunded'){
            $amount=(int)($data['amount']??-1);$currency=strtoupper(trim((string)($data['currency']??'')));
            if($amount!==(int)$intent['amount_minor']||$currency!==(string)$intent['currency'])return array('ok'=>true,'ignored'=>true,'code'=>'partial_refund_requires_review','ignoredEvent'=>array('providerEventId'=>$eventId,'intentId'=>$intent['id'],'type'=>'refund.processed','reasonCode'=>'partial_refund','occurredAt'=>$this->time($data['created_at']??'now')));
        }
        return array('ok'=>true,'event'=>array('providerEventId'=>$eventId,'intentId'=>$intent['id'],'providerPaymentId'=>(string)($transaction['id']??$reference),'type'=>$type,'reasonCode'=>$type==='payment.disputed'?'provider_dispute':null,'occurredAt'=>$this->time($data['created_at']??'now')));
    }
    private function subscriptionDescriptor(array $data){
        $customer=is_array($data['customer']??null)?$data['customer']:array();$plan=is_array($data['plan_object']??null)?$data['plan_object']:array();
        if(!$plan&&is_array($data['plan']??null))$plan=$data['plan'];
        if(!$customer&&is_array($data['subscription']['customer']??null))$customer=$data['subscription']['customer'];
        if(!$plan&&is_array($data['subscription']['plan']??null))$plan=$data['subscription']['plan'];
        $customerId=trim((string)($customer['customer_code']??$data['customer_code']??''));$planId=trim((string)($plan['plan_code']??(is_scalar($data['plan']??null)?$data['plan']:'')??''));
        if($customerId===''||$planId==='')return null;
        return array('providerCustomerId'=>$customerId,'providerPlanId'=>$planId,'providerSubscriptionId'=>(string)($data['subscription_code']??$data['subscription']['subscription_code']??''));
    }
    /**
     * Paystack may deliver subscription.create before charge.success. When that
     * happens the core cannot associate the early event with an intent yet.
     * Resolve the provider-owned subscription during verified charge handling
     * so its cancellation identifier is never lost to webhook ordering.
     */
    private function resolveSubscriptionDescriptor(array $charge,array $descriptor){
        $customer=is_array($charge['customer']??null)?$charge['customer']:array();
        $plan=is_array($charge['plan_object']??null)?$charge['plan_object']:array();
        if(!$plan&&is_array($charge['plan']??null))$plan=$charge['plan'];
        $customerId=filter_var($customer['id']??null,FILTER_VALIDATE_INT,array('options'=>array('min_range'=>1)));
        $planId=filter_var($plan['id']??null,FILTER_VALIDATE_INT,array('options'=>array('min_range'=>1)));
        $path='/subscription?perPage=100';
        if($customerId!==false&&$planId!==false)$path.='&customer='.$customerId.'&plan='.$planId;
        $response=$this->request('GET',$path);
        if(empty($response['ok'])||!is_array($response['data']['data']??null))return $descriptor;
        foreach($response['data']['data'] as $subscription){
            if(!is_array($subscription))continue;
            $candidate=$this->subscriptionDescriptor($subscription);
            if($candidate!==null
                &&$candidate['providerCustomerId']===$descriptor['providerCustomerId']
                &&$candidate['providerPlanId']===$descriptor['providerPlanId']
                &&trim((string)$candidate['providerSubscriptionId'])!=='')return $candidate;
        }
        return $descriptor;
    }
    private function request($method,$path,$payload=null){
        if(!function_exists('curl_init'))return $this->failure('curl_unavailable');
        $handle=curl_init(self::API.$path);$headers=array('Authorization: Bearer '.$this->secret(),'Content-Type: application/json','Cache-Control: no-cache');
        $options=array(CURLOPT_RETURNTRANSFER=>true,CURLOPT_CONNECTTIMEOUT=>10,CURLOPT_TIMEOUT=>30,CURLOPT_HTTPHEADER=>$headers,CURLOPT_CUSTOMREQUEST=>$method);
        if($payload!==null){$json=json_encode($payload,JSON_UNESCAPED_SLASHES);if($json===false)return $this->failure('json_encoding_failed');$options[CURLOPT_POSTFIELDS]=$json;}
        curl_setopt_array($handle,$options);$body=curl_exec($handle);$error=curl_error($handle);$status=(int)curl_getinfo($handle,CURLINFO_RESPONSE_CODE);curl_close($handle);
        if($body===false)return $this->failure('paystack_transport_error',true,$error);$decoded=json_decode((string)$body,true);
        if($status<200||$status>=300||!is_array($decoded)||empty($decoded['status']))return $this->failure('paystack_api_rejected_'.$status,$status>=500||$status===429,is_array($decoded)?($decoded['message']??null):null);
        return array('ok'=>true,'data'=>$decoded);
    }
    private function secret(){return trim((string)$this->config->getValue('PAYMENT_PAYSTACK_SECRET_KEY','payment-service'));}
    private function reservedEmailDomain($email){
        $domain=strtolower((string)substr(strrchr((string)$email,'@')?:'',1));
        return $domain===''||preg_match('/(?:^|\.)(?:test|invalid|example|localhost)$/',$domain)===1;
    }
    private function httpsUrl($url){return filter_var($url,FILTER_VALIDATE_URL)&&parse_url($url,PHP_URL_SCHEME)==='https';}
    private function time($value){try{return(new DateTimeImmutable((string)$value))->format('Y-m-d H:i:s');}catch(Throwable $failure){return date('Y-m-d H:i:s');}}
    private function failure($code,$retryable=false,$detail=null){return array('ok'=>false,'code'=>$code,'retryable'=>(bool)$retryable,'detail'=>$detail);}
}
?>
