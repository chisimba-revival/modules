<?php
$module=dirname(__DIR__);
$service=file_get_contents($module.'/classes/paymentservice_class_inc.php');
$controller=file_get_contents($module.'/controller.php');
$events=file_get_contents($module.'/sql/tbl_payment_service_events.sql');
$catalogue=file_get_contents($module.'/templates/content/catalogue_tpl.php');
$expect=function($condition,$message){ if(!$condition){ throw new RuntimeException($message); } };
$expect(strpos($service,'extends ChisimbaObject')!==false
    && !preg_match('/extends\s+object\b/i',$service),
    'Runtime payment services must use the PHP 8 Chisimba base class.');
$expect(strpos($service,'recordBrowserReturn')!==false && strpos($service,"'awaiting_verified_provider_event'")!==false,
    'Browser returns must remain non-authoritative.');
$expect(strpos($service,'verifyAndNormalize')!==false && strpos($service,"'unverified_event'")!==false,
    'Provider events must be verified before processing.');
$expect(strpos($controller,"array('yocowebhook','paystackwebhook')")!==false
    && strpos($controller,"file_get_contents('php://input')")!==false
    && strpos($controller,"http_response_code(\$accepted?200:(!empty(\$result['retryable'])?503:403))")!==false,
    'The webhook endpoint must accept unauthenticated delivery while rejecting unverified events.');
$expect(strpos($events,"'unique' => TRUE")!==false && strpos($events,"'provider_event_id'")!==false,
    'Provider event identity must be unique and durable.');
$expect(strpos($service,"'duplicate_event_ignored'")!==false && strpos($service,"'out_of_order_event_ignored'")!==false,
    'Duplicate and out-of-order event outcomes must be explicit.');
$expect(strpos($service,"'provider_reference'=>'fake-checkout-'.\$intent['id'].':delayed'")!==false,
    'Delayed fake events must retain an explicit reconciliation marker.');
$expect(strpos($service, 'applyAutomaticAdmission') !== false
    && strpos($service, "\$event['type'] === 'payment.succeeded'") !== false
    && strpos($service, 'recordBrowserReturn') < strpos($service, 'applyAutomaticAdmission'),
    'Private admission must be driven by verified success, with duplicate delivery available for recovery.');
$expect(strpos($service,"'payment.failed' => 'failed'")!==false
    && strpos($service,"'payment.refunded' => 'refunded'")!==false
    && strpos($service,"'payment.reversed' => 'reversed'")!==false
    && strpos($service,"'payment.disputed' => 'disputed'")!==false,
    'Canonical unhappy payment states must be represented.');
$expect(str_contains($service,'reverseFulfilment')&&str_contains($service,"array('refunded','reversed')"),'Refunds and reversals must end the fulfilment created by the payment.');
$expect(stripos($service,'card_number')===false && stripos($service,'cvv')===false,
    'The payment core must not store raw card data.');
$expect(str_contains($controller,"getContextDetails((string)\$product['purpose_id'])")
    && str_contains($controller,"getContextLecturers((string)\$product['purpose_id'])")
    && str_contains($catalogue,'course-card payment-review-course'),
    'A private-course purchase must retain its course identity and lecturer confidence cues.');
$expect(str_contains($catalogue,'Once-off payment')
    && str_contains($catalogue,'Continue securely with <?=$provider?>')
    && str_contains($catalogue,'paymentLearnerName')
    && str_contains($catalogue,'never receives or stores your card details'),
    'Checkout review copy must explain the learner, billing model and secure handoff plainly.');
$expect(str_contains($service,"'paystack'")&&str_contains($service,'preferredProvider')
    &&str_contains($controller,"case 'paystackwebhook'")&&str_contains($controller,'reconcileIntent'),
    'Paystack must be selectable, webhook-driven and independently verified on browser return.');
$expect(str_contains($controller,"'email'=>\$this->user->email(\$this->user->userId())"),
    'Checkout must resolve email from the canonical user record when legacy sessions omit it.');
$expect(str_contains($controller,'effectiveTier($userId)')
    && str_contains($controller,'tierIncludes($effectiveTier'),
    'The catalogue must not sell a membership tier already included by the learner current tier.');
$expect(str_contains($catalogue,'payment-review-product--course')
    && str_contains($catalogue,'payment-review-product--membership'),
    'Each order must remain visually grouped with its own optional course preview.');
$expect(str_contains($service,'ensureRenewalIntent')&&str_contains($service,'rememberSubscription')
    &&str_contains($service,'payment.renewal_intent_created'),
    'Every recurring charge must receive its own idempotent intent and durable subscription mapping.');
$paystack=file_get_contents($module.'/classes/paystackpaymentprovider_class_inc.php');
$expect(str_contains($paystack,'resolveSubscriptionDescriptor')
    && str_contains($paystack,"'/subscription?perPage=100'")
    && str_contains($paystack,"empty(\$descriptor['providerSubscriptionId'])"),
    'A verified recurring charge must recover the provider subscription code when subscription.create arrived first.');
$stores=file_get_contents($module.'/classes/dbpaymentintents_class_inc.php').file_get_contents($module.'/classes/dbpaymentevents_class_inc.php').file_get_contents($module.'/classes/dbpayments_class_inc.php');
$expect(!preg_match('/->update\s*\(\s*array\s*\(/',$stores),'Payment stores must use the PHP 8 three-argument dbTable update contract.');
fwrite(STDOUT,"PASS: provider-neutral payment service contract\n");
?>
