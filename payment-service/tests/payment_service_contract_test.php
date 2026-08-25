<?php
$module=dirname(__DIR__);
$service=file_get_contents($module.'/classes/paymentservice_class_inc.php');
$events=file_get_contents($module.'/sql/tbl_payment_service_events.sql');
$expect=function($condition,$message){ if(!$condition){ throw new RuntimeException($message); } };
$expect(strpos($service,'extends ChisimbaObject')!==false
    && !preg_match('/extends\s+object\b/i',$service),
    'Runtime payment services must use the PHP 8 Chisimba base class.');
$expect(strpos($service,'recordBrowserReturn')!==false && strpos($service,"'awaiting_verified_provider_event'")!==false,
    'Browser returns must remain non-authoritative.');
$expect(strpos($service,'verifyAndNormalize')!==false && strpos($service,"'unverified_event'")!==false,
    'Provider events must be verified before processing.');
$expect(strpos($events,"'unique' => TRUE")!==false && strpos($events,"'provider_event_id'")!==false,
    'Provider event identity must be unique and durable.');
$expect(strpos($service,"'duplicate_event_ignored'")!==false && strpos($service,"'out_of_order_event_ignored'")!==false,
    'Duplicate and out-of-order event outcomes must be explicit.');
$expect(strpos($service, 'applyAutomaticAdmission') !== false
    && strpos($service, "\$event['type'] === 'payment.succeeded'") !== false
    && strpos($service, 'recordBrowserReturn') < strpos($service, 'applyAutomaticAdmission'),
    'Private admission must be driven by verified success, with duplicate delivery available for recovery.');
$expect(strpos($service,"'payment.failed' => 'failed'")!==false
    && strpos($service,"'payment.refunded' => 'refunded'")!==false
    && strpos($service,"'payment.reversed' => 'reversed'")!==false
    && strpos($service,"'payment.disputed' => 'disputed'")!==false,
    'Canonical unhappy payment states must be represented.');
$expect(stripos($service,'card_number')===false && stripos($service,'cvv')===false,
    'The payment core must not store raw card data.');
$stores=file_get_contents($module.'/classes/dbpaymentintents_class_inc.php').file_get_contents($module.'/classes/dbpaymentevents_class_inc.php').file_get_contents($module.'/classes/dbpayments_class_inc.php');
$expect(!preg_match('/->update\s*\(\s*array\s*\(/',$stores),'Payment stores must use the PHP 8 three-argument dbTable update contract.');
fwrite(STDOUT,"PASS: provider-neutral payment service contract\n");
?>
