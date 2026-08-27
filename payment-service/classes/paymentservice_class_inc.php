<?php
/** Provider-neutral payment core. Browser returns never confirm payment. */
if (empty($GLOBALS['kewl_entry_point_run'])) { die('You cannot view this page directly'); }

class paymentservice extends ChisimbaObject
{
    private const PURPOSES = array('membership', 'private_course');
    private const PROVIDERS = array('fake', 'yoco', 'paystack');
    private const EVENT_STATES = array(
        'payment.succeeded' => 'succeeded',
        'payment.failed' => 'failed',
        'checkout.abandoned' => 'abandoned',
        'payment.refunded' => 'refunded',
        'payment.reversed' => 'reversed',
        'payment.disputed' => 'disputed',
    );
    private const TERMINAL_PRECEDENCE = array('created'=>0,'awaiting_approval'=>1,'processing'=>2,'failed'=>3,'abandoned'=>3,'succeeded'=>4,'disputed'=>5,'refunded'=>6,'reversed'=>6);

    public function init()
    {
        $this->intents = $this->getObject('dbpaymentintents');
        $this->events = $this->getObject('dbpaymentevents');
        $this->payments = $this->getObject('dbpayments');
        $this->catalog = $this->getObject('paymentcatalogservice');
        $this->subscriptions = $this->getObject('dbpaymentsubscriptions');
        $this->users = $this->getObject('userservice', 'security');
        $this->accountEvents = $this->getObject('accounteventservice', 'account-event-service');
    }

    /** Build an intent from the canonical effective price, never browser amounts. */
    public function createIntentFromProduct($userId,$productCode,$provider,$idempotencyKey,$correlationId)
    {
        $product=$this->catalog->purchasable($productCode);
        if($product===NULL) return $this->result(FALSE,'product_not_purchasable');
        if($product['purpose_type']==='private_course') {
            try { if($this->getObject('privateadmissionservice','membership-service')->isAdmitted($product['purpose_id'],$userId)) return $this->result(FALSE,'already_has_access'); }
            catch(Throwable $failure) { return $this->result(FALSE,'admission_service_unavailable'); }
        }
        return $this->createIntent(array(
            'userId'=>$userId,'purposeType'=>$product['purpose_type'],'purposeId'=>$product['purpose_id'],
            'productCode'=>$product['code'],'priceVersion'=>$product['price']['version_code'],
            'amountMinor'=>$product['price']['amount_minor'],'currency'=>$product['price']['currency'],
            'provider'=>$provider,'idempotencyKey'=>$idempotencyKey,'correlationId'=>$correlationId,
        ));
    }

    public function intent($intentId) { return $this->intents->byId($this->hexId($intentId)); }
    public function operations($limit=200) { return array('intents'=>$this->intents->recent($limit),'payments'=>$this->payments->recent($limit),'events'=>$this->events->recent($limit),'subscriptions'=>$this->subscriptions->recent($limit)); }
    public function providerAvailable($code) { $provider=$this->provider($code); return $provider!==NULL&&$provider->isAvailable(); }
    public function preferredProvider()
    {
        $config=$this->getObject('dbsysconfig','sysconfig');
        $preferred=strtolower(trim((string)$config->getValue('PAYMENT_DEFAULT_PROVIDER','payment-service')));
        foreach(array($preferred,'paystack','yoco','fake') as $code) {
            if(in_array($code,self::PROVIDERS,true)&&$this->providerAvailable($code)) return $code;
        }
        return 'fake';
    }

    /** Development adapter only; events still pass verification and idempotency. */
    public function runFakeScenario($intentId,$scenario)
    {
        $intent=$this->intents->byId($this->hexId($intentId));
        if($intent===NULL||$intent['provider_code']!=='fake') return $this->result(FALSE,'fake_checkout_required',$intentId);
        if($scenario==='delayed') {
            $this->intents->transition($intent['id'], 'awaiting_approval', array(
                'provider_reference'=>'fake-checkout-'.$intent['id'].':delayed',
                'updated_at'=>date('Y-m-d H:i:s'),
            ));
            return array('ok'=>true,'code'=>'fake_event_delayed','intentId'=>$intent['id'],'results'=>array());
        }
        $results=array();
        foreach($this->getObject('fakepaymentprovider')->script($scenario,$intent['id'],date('Y-m-d H:i:s')) as $envelope) $results[]=$this->receiveProviderEvent('fake',$envelope);
        return array('ok'=>count($results)>0,'code'=>count($results)?'fake_events_processed':'no_fake_events','intentId'=>$intent['id'],'results'=>$results);
    }

    public function deliverDelayedFakeEvent($intentId)
    {
        $intent=$this->intents->byId($this->hexId($intentId));
        if($intent===NULL||$intent['provider_code']!=='fake'||$intent['state']!=='awaiting_approval'||!str_ends_with((string)$intent['provider_reference'],':delayed')) return $this->result(FALSE,'intent_not_pending',$intentId);
        $script=$this->getObject('fakepaymentprovider')->script('success',$intent['id'],date('Y-m-d H:i:s'));
        return count($script)?$this->receiveProviderEvent('fake',$script[0]):$this->result(FALSE,'fake_event_unavailable',$intent['id']);
    }

    public function createIntent(array $input)
    {
        $values = $this->normaliseIntent($input);
        if ($values === NULL) { return $this->result(FALSE, 'invalid_intent'); }
        if ($this->users->findByUserId($values['user_id']) === NULL) { return $this->result(FALSE, 'user_not_found'); }
        $existing = $this->intents->byIdempotency($values['idempotency_key']);
        if ($existing !== NULL) { return $this->result(TRUE, 'already_created', $existing['id']); }
        $values['id'] = bin2hex(random_bytes(16));
        $values['state'] = 'created';
        $values['created_at'] = $values['updated_at'] = date('Y-m-d H:i:s');
        if ($this->intents->create($values) === FALSE) { return $this->result(FALSE, 'intent_failed'); }
        $this->audit('payment.intent_created', $values, 'requested');
        return $this->result(TRUE, 'intent_created', $values['id']);
    }

    public function startCheckout($intentId, array $options = array())
    {
        $intent = $this->intents->byId($this->hexId($intentId));
        if ($intent === NULL) { return $this->result(FALSE, 'intent_not_found'); }
        if ($intent['state'] !== 'created') { return $this->result(TRUE, 'checkout_already_started', $intent['id']); }
        $provider = $this->provider($intent['provider_code']);
        if ($provider === NULL || !$provider->isAvailable()) { return $this->result(FALSE, 'provider_unavailable', $intent['id']); }
        if($intent['provider_code']!=='fake') $options['product']=$this->catalog->productVersion($intent['product_code'],$intent['price_version']);
        $checkout = $provider->createCheckout($intent, $intent['provider_code']==='fake' ? ($options['scenario'] ?? 'success') : $options);
        if (empty($checkout['ok'])) { return array_merge($this->result(FALSE, $checkout['code'] ?? 'checkout_failed', $intent['id']), array('retryable'=>!empty($checkout['retryable']))); }
        $this->intents->transition($intent['id'], 'created', array(
            'state'=>'awaiting_approval',
            'provider_reference'=>$checkout['providerReference'] ?? NULL,
            'updated_at'=>date('Y-m-d H:i:s'),
        ));
        return array_merge($this->result(TRUE, 'approval_required', $intent['id']), array('approvalUrl'=>$checkout['approvalUrl'] ?? NULL));
    }

    /** Record navigation only; this method intentionally cannot change payment state. */
    public function recordBrowserReturn($intentId, $providerReference = '')
    {
        $intent = $this->intents->byId($this->hexId($intentId));
        if ($intent === NULL) { return $this->result(FALSE, 'intent_not_found'); }
        $this->audit('payment.browser_returned', $intent, 'requested', array('provider_reference_present'=>trim((string)$providerReference)!==''));
        return $this->result(TRUE, 'awaiting_verified_provider_event', $intent['id']);
    }

    /** Server verification can improve return-page feedback; browser navigation itself remains non-authoritative. */
    public function reconcileIntent($intentId)
    {
        $intent=$this->intents->byId($this->hexId($intentId));
        if($intent===NULL) return $this->result(FALSE,'intent_not_found');
        $provider=$this->provider($intent['provider_code']);
        if($provider===NULL||!method_exists($provider,'verifyPayment')) return $this->result(TRUE,'verification_deferred',$intent['id']);
        $verified=$provider->verifyPayment($intent);
        if(empty($verified['ok'])||!is_array($verified['event']??null)) return array_merge($this->result(FALSE,$verified['code']??'verification_failed',$intent['id']),array('retryable'=>!empty($verified['retryable'])));
        return $this->processVerifiedEvent($intent['provider_code'],$verified['event']);
    }

    public function receiveProviderEvent($providerCode, array $envelope)
    {
        $provider = $this->provider($providerCode);
        if ($provider === NULL) { return $this->result(FALSE, 'provider_unavailable'); }
        $verified = $provider->verifyAndNormalize($envelope);
        if(!empty($verified['ok'])&&is_array($verified['subscriptionUpdate']??null)) {
            return $this->updateSubscriptionState($providerCode,$verified['subscriptionUpdate']);
        }
        if (!empty($verified['ok']) && !empty($verified['ignored'])) {
            $ignored=$verified['ignoredEvent']??NULL;
            if(is_array($ignored)&&$this->text($ignored['providerEventId']??NULL,191)!==NULL
                &&$this->hexId($ignored['intentId']??NULL)!==NULL
                &&$this->text($ignored['type']??NULL,96)!==NULL
                &&$this->timestamp($ignored['occurredAt']??NULL)!==NULL) {
                $claim=$this->events->claim(array(
                    'provider_code'=>$providerCode,'provider_event_id'=>$ignored['providerEventId'],
                    'intent_id'=>$ignored['intentId'],'event_type'=>$ignored['type'],
                    'reason_code'=>$ignored['reasonCode']??NULL,'occurred_at'=>$ignored['occurredAt'],
                ));
                if(!empty($claim['ok'])&&empty($claim['duplicate'])) $this->events->complete($claim['id'],$verified['code']??'verified_event_ignored');
            }
            return $this->result(TRUE, $verified['code'] ?? 'verified_event_ignored');
        }
        if (empty($verified['ok']) || !is_array($verified['event'] ?? NULL)) {
            return array_merge($this->result(FALSE, 'unverified_event'),array('retryable'=>!empty($verified['retryable'])));
        }
        return $this->processVerifiedEvent($providerCode,$verified['event']);
    }

    private function processVerifiedEvent($providerCode,array $event)
    {
        if(is_array($event['renewal']??null)) {
            $renewal=$this->ensureRenewalIntent($providerCode,$event['renewal']);
            if(empty($renewal['ok'])) return $renewal;
            $event['intentId']=$renewal['intentId'];
        }
        if (!$this->validEvent($event)) { return $this->result(FALSE, 'invalid_provider_event'); }
        $claim = $this->events->claim(array(
            'provider_code'=>$providerCode,
            'provider_event_id'=>$event['providerEventId'],
            'intent_id'=>$event['intentId'],
            'event_type'=>$event['type'],
            'reason_code'=>$event['reasonCode'] ?: NULL,
            'occurred_at'=>$event['occurredAt'],
        ));
        if (empty($claim['ok'])) { return $this->result(FALSE, 'event_claim_failed', $event['intentId']); }
        if (!empty($claim['duplicate'])) {
            $intent = $this->intents->byId($event['intentId']);
            if ($event['type'] === 'payment.succeeded' && is_array($intent)) {
                $this->applyFulfilment($intent, $event['providerPaymentId']);
            }
            if(is_array($event['subscription']??null)) $this->rememberSubscription($providerCode,$event);
            return $this->result(TRUE, 'duplicate_event_ignored', $event['intentId']);
        }
        $result = $this->applyEvent($providerCode, $event);
        if(!empty($result['ok'])&&is_array($event['subscription']??null)) $this->rememberSubscription($providerCode,$event);
        $this->events->complete($claim['id'], $result['code']);
        return $result;
    }

    private function ensureRenewalIntent($providerCode,array $renewal)
    {
        $base=$this->intents->byId($this->hexId($renewal['baseIntentId']??null));
        $reference=$this->text($renewal['reference']??null,191);
        if($base===NULL||$reference===NULL) return $this->result(FALSE,'invalid_subscription_renewal');
        $key=$providerCode.'-renewal:'.$reference;
        $existing=$this->intents->byIdempotency($key);
        if(is_array($existing)) return $this->result(TRUE,'renewal_intent_exists',$existing['id']);
        $values=$base;
        $values['id']=bin2hex(random_bytes(16));$values['state']='awaiting_approval';
        $values['provider_reference']=$reference;$values['failure_code']=NULL;
        $values['idempotency_key']=$key;
        $values['correlation_id']='renewal:'.substr(hash('sha256',$reference),0,48);
        $values['created_at']=$values['updated_at']=date('Y-m-d H:i:s');
        if($this->intents->create($values)===FALSE) return $this->result(FALSE,'renewal_intent_failed');
        $this->audit('payment.renewal_intent_created',$values,'requested');
        return $this->result(TRUE,'renewal_intent_created',$values['id']);
    }

    private function rememberSubscription($providerCode,array $event)
    {
        $descriptor=$event['subscription'];$intent=$this->intents->byId($event['intentId']);
        if(!is_array($intent)||$intent['purpose_type']!=='membership') return;
        $customer=$this->text($descriptor['providerCustomerId']??null,191);
        $plan=$this->text($descriptor['providerPlanId']??null,191);
        if($customer===NULL||$plan===NULL)return;
        $this->subscriptions->remember(array(
            'provider_code'=>$providerCode,
            'provider_subscription_id'=>$this->text($descriptor['providerSubscriptionId']??null,191),
            'provider_customer_id'=>$customer,'provider_plan_id'=>$plan,
            'base_intent_id'=>$intent['id'],'product_code'=>$intent['product_code'],'state'=>'active',
        ));
    }

    private function updateSubscriptionState($providerCode,array $update)
    {
        $descriptor=is_array($update['descriptor']??null)?$update['descriptor']:array();
        $customer=$this->text($descriptor['providerCustomerId']??null,191);
        $plan=$this->text($descriptor['providerPlanId']??null,191);
        $state=$this->enum($update['state']??null,array('active','non_renewing','disabled'));
        if($customer===NULL||$plan===NULL||$state===NULL)return $this->result(FALSE,'invalid_subscription_update');
        $existing=$this->subscriptions->byCustomerPlan($providerCode,$customer,$plan);
        if(!is_array($existing))return $this->result(TRUE,'subscription_update_deferred');
        $saved=$this->subscriptions->remember(array(
            'provider_code'=>$providerCode,'provider_subscription_id'=>$this->text($descriptor['providerSubscriptionId']??null,191),
            'provider_customer_id'=>$customer,'provider_plan_id'=>$plan,'base_intent_id'=>$existing['base_intent_id'],
            'product_code'=>$existing['product_code'],'state'=>$state,
        ));
        return $this->result($saved!==NULL,$saved!==NULL?'subscription_updated':'subscription_update_failed',$existing['base_intent_id']);
    }

    private function applyEvent($providerCode, array $event)
    {
        $intent = $this->intents->byId($event['intentId']);
        if ($intent === NULL) { return $this->result(FALSE, 'intent_not_found', $event['intentId']); }
        $next = self::EVENT_STATES[$event['type']];
        $currentRank = self::TERMINAL_PRECEDENCE[$intent['state']] ?? -1;
        $nextRank = self::TERMINAL_PRECEDENCE[$next];
        // Recovery is explicit; otherwise late events may not downgrade a later state.
        $recovery = $intent['state'] === 'failed' && $next === 'succeeded';
        if (!$recovery && $nextRank < $currentRank) { return $this->result(TRUE, 'out_of_order_event_ignored', $intent['id']); }
        $updated = $this->intents->transition($intent['id'], $intent['state'], array(
            'state'=>$next,
            'failure_code'=>$next === 'failed' ? $event['reasonCode'] : NULL,
            // Keep the checkout reference stable so later refund webhooks can be correlated.
            'provider_reference'=>$intent['provider_reference'] ?? $event['providerPaymentId'],
            'updated_at'=>date('Y-m-d H:i:s'),
        ));
        if ($updated === FALSE) { return $this->result(FALSE, 'state_transition_failed', $intent['id']); }
        if (in_array($next, array('succeeded','refunded','reversed','disputed'), TRUE)) {
            $this->payments->record(array(
                'intent_id'=>$intent['id'], 'provider_code'=>$providerCode,
                'provider_payment_id'=>$event['providerPaymentId'], 'state'=>$next,
                'amount_minor'=>$intent['amount_minor'], 'currency'=>$intent['currency'],
            ));
        }
        $intent['state']=$next;
        $this->audit('payment.' . $next, $intent, $next === 'failed' ? 'failed' : 'succeeded', array('reason_code'=>$event['reasonCode']));
        if ($next === 'succeeded') {
            $fulfilment = $this->applyFulfilment($intent, $event['providerPaymentId']);
            if (empty($fulfilment['ok']) && ($fulfilment['code'] ?? '') !== 'automatic_payment_not_configured') {
                return $this->result(TRUE, 'payment_succeeded_fulfilment_pending', $intent['id']);
            }
        }
        if (in_array($next,array('refunded','reversed'),TRUE)) {
            $originalPayment=method_exists($this->payments,'successfulReferenceForIntent')
                ? $this->payments->successfulReferenceForIntent($intent['id']) : NULL;
            $this->reverseFulfilment($intent,$originalPayment?:$event['providerPaymentId']);
        }
        return $this->result(TRUE, 'payment_' . $next, $intent['id']);
    }

    private function applyAutomaticAdmission(array $intent, $paymentReference)
    {
        try {
            return $this->getObject('privateadmissionservice', 'membership-service')
                ->admitConfirmedPayment($intent['purpose_id'], $intent['user_id'],
                    (string) $paymentReference, $intent['correlation_id']);
        } catch (Throwable $failure) {
            return array('ok' => false, 'code' => 'admission_service_unavailable');
        }
    }

    private function applyFulfilment(array $intent,$paymentReference)
    {
        if($intent['purpose_type']==='private_course') return $this->applyAutomaticAdmission($intent,$paymentReference);
        if($intent['purpose_type']!=='membership') return array('ok'=>false,'code'=>'unsupported_fulfilment');
        $product=$this->catalog->productVersion($intent['product_code'],$intent['price_version']);
        if(!is_array($product)||empty($product['duration_months'])) return array('ok'=>false,'code'=>'product_fulfilment_unavailable');
        try {
            $memberships=$this->getObject('membershipservice','membership-service');
            $start=new DateTimeImmutable('today');
            $coverageEnd=$memberships->latestCoverageEnd($intent['user_id'],$intent['purpose_id']);
            if($coverageEnd!==null) {
                $candidate=(new DateTimeImmutable($coverageEnd))->modify('+1 second');
                if($candidate>$start) $start=$candidate;
            }
            $end=$start->modify('+'.(int)$product['duration_months'].' months')->modify('-1 second');
            return $memberships->createPeriod(array(
                'userId'=>$intent['user_id'],'tier'=>$intent['purpose_id'],'state'=>'active',
                'startsAt'=>$start->format('Y-m-d H:i:s'),'endsAt'=>$end->format('Y-m-d H:i:s'),
                'sourceType'=>'payment','sourceReference'=>(string)$paymentReference,
                'idempotencyKey'=>'payment-intent:'.$intent['id'],'correlationId'=>$intent['correlation_id'],
            ));
        } catch(Throwable $failure) { return array('ok'=>false,'code'=>'membership_service_unavailable'); }
    }

    private function reverseFulfilment(array $intent,$paymentReference)
    {
        try {
            if($intent['purpose_type']==='membership') return $this->getObject('membershipservice','membership-service')->endPeriodByIdempotency('payment-intent:'.$intent['id'],$intent['correlation_id']);
            if($intent['purpose_type']==='private_course') return $this->getObject('privateadmissionservice','membership-service')->revokeConfirmedPayment($intent['purpose_id'],$intent['user_id'],$paymentReference,$intent['correlation_id']);
        } catch(Throwable $failure) { return array('ok'=>false,'code'=>'fulfilment_reversal_pending'); }
        return array('ok'=>false,'code'=>'unsupported_fulfilment');
    }

    private function provider($code)
    {
        if ($code === 'fake') { return $this->getObject('fakepaymentprovider'); }
        if ($code === 'yoco') { return $this->getObject('yocopaymentprovider'); }
        if ($code === 'paystack') { return $this->getObject('paystackpaymentprovider'); }
        return NULL;
    }

    private function normaliseIntent(array $input)
    {
        $amount = filter_var($input['amountMinor'] ?? NULL, FILTER_VALIDATE_INT, array('options'=>array('min_range'=>1)));
        $values = array(
            'user_id'=>$this->text($input['userId'] ?? NULL,25),
            'purpose_type'=>$this->enum($input['purposeType'] ?? NULL,self::PURPOSES),
            'purpose_id'=>$this->text($input['purposeId'] ?? NULL,191),
            'product_code'=>$this->identifier($input['productCode'] ?? NULL,96),
            'price_version'=>$this->identifier($input['priceVersion'] ?? NULL,64),
            'amount_minor'=>$amount,
            'currency'=>strtoupper((string)($input['currency'] ?? '')),
            'provider_code'=>$this->enum($input['provider'] ?? NULL,self::PROVIDERS),
            'idempotency_key'=>$this->text($input['idempotencyKey'] ?? NULL,191),
            'correlation_id'=>$this->identifier($input['correlationId'] ?? NULL,64),
        );
        return in_array(NULL,$values,TRUE) || $amount===FALSE || !preg_match('/^[A-Z]{3}$/',$values['currency']) ? NULL : $values;
    }

    private function validEvent(array $event)
    {
        return $this->text($event['providerEventId'] ?? NULL,191)!==NULL
            && $this->hexId($event['intentId'] ?? NULL)!==NULL
            && $this->text($event['providerPaymentId'] ?? NULL,191)!==NULL
            && isset(self::EVENT_STATES[$event['type'] ?? ''])
            && $this->timestamp($event['occurredAt'] ?? NULL)!==NULL
            && (($event['reasonCode'] ?? NULL)===NULL || $this->identifier($event['reasonCode'],96)!==NULL);
    }

    private function audit($type,array $intent,$outcome,array $metadata=array())
    {
        $this->accountEvents->append(array(
            'eventType'=>$type,'subjectType'=>'user','subjectId'=>$intent['user_id'],
            'actorType'=>'service','actorId'=>'payment-service','outcome'=>$outcome,
            'correlationId'=>$intent['correlation_id'],'sourceService'=>'payment-service',
            'metadata'=>array_merge(array('intent_id'=>$intent['id'],'purpose_type'=>$intent['purpose_type'],'purpose_id'=>$intent['purpose_id'],'state'=>$intent['state']),$metadata),
        ));
    }
    private function enum($v,array $a) { $v=is_scalar($v)?strtolower(trim((string)$v)):''; return in_array($v,$a,TRUE)?$v:NULL; }
    private function identifier($v,$m) { $v=is_scalar($v)?trim((string)$v):''; return $v!==''&&strlen($v)<=$m&&preg_match('/^[A-Za-z0-9][A-Za-z0-9_.:-]*$/',$v)?$v:NULL; }
    private function text($v,$m) { $v=is_scalar($v)?trim((string)$v):''; return $v!==''&&strlen($v)<=$m&&!preg_match('/[\x00-\x1F\x7F]/',$v)?$v:NULL; }
    private function hexId($v) { $v=is_scalar($v)?strtolower(trim((string)$v)):''; return preg_match('/^[a-f0-9]{32}$/',$v)?$v:NULL; }
    private function timestamp($v) { $d=DateTimeImmutable::createFromFormat('!Y-m-d H:i:s',(string)$v); return $d&&$d->format('Y-m-d H:i:s')===(string)$v?(string)$v:NULL; }
    private function result($ok,$code,$intentId=NULL) { return array('ok'=>(bool)$ok,'code'=>$code,'intentId'=>$intentId); }
}
?>
