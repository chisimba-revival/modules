<?php
/** Deterministic provider used to exercise unhappy payment paths without money. */
if (empty($GLOBALS['kewl_entry_point_run'])) { die('You cannot view this page directly'); }

class fakepaymentprovider extends object
{
    private const SCENARIOS = array(
        'success', 'card_decline', 'insufficient_funds', 'expired_card', 'timeout',
        'abandoned_checkout', 'delayed', 'duplicate', 'out_of_order', 'recovery',
        'refund', 'reversal', 'dispute'
    );

    public function code() { return 'fake'; }
    public function isAvailable() { return TRUE; }

    public function createCheckout(array $intent, $scenario = 'success')
    {
        $scenario = strtolower(trim((string) $scenario));
        if (!in_array($scenario, self::SCENARIOS, TRUE)) {
            return array('ok' => FALSE, 'code' => 'invalid_scenario');
        }
        if ($scenario === 'timeout') {
            return array('ok' => FALSE, 'code' => 'provider_timeout', 'retryable' => TRUE);
        }
        return array(
            'ok' => TRUE,
            'code' => 'approval_required',
            'providerReference' => 'fake-checkout-' . $intent['id'],
            'approvalUrl' => 'fake-payment://checkout/' . $intent['id'] . '?scenario=' . $scenario,
        );
    }

    /** Return a stable event script; delivery order may differ from occurredAt. */
    public function script($scenario, $intentId, $baseTime = '2026-08-25 10:00:00')
    {
        $scenario = strtolower(trim((string) $scenario));
        if (!in_array($scenario, self::SCENARIOS, TRUE) || $scenario === 'timeout') {
            return array();
        }
        $failure = array(
            'card_decline' => 'card_declined',
            'insufficient_funds' => 'insufficient_funds',
            'expired_card' => 'expired_card',
        );
        if (isset($failure[$scenario])) {
            return array($this->event($intentId, 1, 'payment.failed', $failure[$scenario], $baseTime));
        }
        if ($scenario === 'abandoned_checkout') {
            return array($this->event($intentId, 1, 'checkout.abandoned', 'customer_abandoned', $baseTime));
        }
        $success = $this->event($intentId, 1, 'payment.succeeded', NULL, $baseTime);
        if ($scenario === 'success') { return array($success); }
        if ($scenario === 'delayed') { $success['deliveryDelaySeconds'] = 3600; return array($success); }
        if ($scenario === 'duplicate') { return array($success, $success); }
        if ($scenario === 'out_of_order') {
            return array(
                $this->event($intentId, 2, 'payment.refunded', NULL, '2026-08-25 10:05:00'),
                $success,
            );
        }
        if ($scenario === 'recovery') {
            return array(
                $this->event($intentId, 1, 'payment.failed', 'card_declined', $baseTime),
                $this->event($intentId, 2, 'payment.succeeded', NULL, '2026-08-25 10:05:00'),
            );
        }
        $eventType = array(
            'refund' => 'payment.refunded',
            'reversal' => 'payment.reversed',
            'dispute' => 'payment.disputed',
        )[$scenario];
        return array($success, $this->event($intentId, 2, $eventType, NULL, '2026-08-25 10:05:00'));
    }

    public function verifyAndNormalize(array $envelope)
    {
        $event = $envelope['event'] ?? NULL;
        $signature = is_scalar($envelope['signature'] ?? NULL) ? (string) $envelope['signature'] : '';
        if (!is_array($event) || !hash_equals($this->signature($event), $signature)) {
            return array('ok' => FALSE, 'code' => 'unverified_event');
        }
        return array('ok' => TRUE, 'event' => $event);
    }

    private function event($intentId, $sequence, $type, $reason, $occurredAt)
    {
        $event = array(
            'providerEventId' => 'fake-' . $intentId . '-' . $sequence,
            'intentId' => $intentId,
            'providerPaymentId' => 'fake-payment-' . $intentId,
            'type' => $type,
            'reasonCode' => $reason,
            'occurredAt' => $occurredAt,
        );
        return array('event' => $event, 'signature' => $this->signature($event));
    }

    private function signature(array $event)
    {
        return hash_hmac('sha256', json_encode($event, JSON_UNESCAPED_SLASHES), 'chisimba-fake-provider-contract-v1');
    }
}
?>
