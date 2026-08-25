<?php
/** Provider-neutral payment core. Browser returns never confirm payment. */
if (empty($GLOBALS['kewl_entry_point_run'])) { die('You cannot view this page directly'); }

class paymentservice extends object
{
    private const PURPOSES = array('membership', 'private_course');
    private const PROVIDERS = array('fake');
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
        $this->users = $this->getObject('userservice', 'security');
        $this->accountEvents = $this->getObject('accounteventservice', 'account-event-service');
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
        $checkout = $provider->createCheckout($intent, $options['scenario'] ?? 'success');
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

    public function receiveProviderEvent($providerCode, array $envelope)
    {
        $provider = $this->provider($providerCode);
        if ($provider === NULL) { return $this->result(FALSE, 'provider_unavailable'); }
        $verified = $provider->verifyAndNormalize($envelope);
        if (empty($verified['ok']) || !is_array($verified['event'] ?? NULL)) { return $this->result(FALSE, 'unverified_event'); }
        $event = $verified['event'];
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
        if (!empty($claim['duplicate'])) { return $this->result(TRUE, 'duplicate_event_ignored', $event['intentId']); }
        $result = $this->applyEvent($providerCode, $event);
        $this->events->complete($claim['id'], $result['code']);
        return $result;
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
            'provider_reference'=>$event['providerPaymentId'],
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
        return $this->result(TRUE, 'payment_' . $next, $intent['id']);
    }

    private function provider($code)
    {
        return $code === 'fake' ? $this->getObject('fakepaymentprovider') : NULL;
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
