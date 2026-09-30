<?php
// Reuse the canonical verified-event harness and its private-course regression.
require __DIR__.'/automatic_private_admission_test.php';
$intents->row=array_merge($intent,['purpose_type'=>'contribution','state'=>'awaiting_approval']);
$events->duplicate=false;
$admissionCount=count($admissions->calls); $revocationCount=count($admissions->revocations);
$event['type']='payment.succeeded'; $event['providerEventId']='contribution-success';
$result=$service->receiveProviderEvent('fake',['event'=>$event]);
$expect($result['code']==='payment_succeeded','Contribution must complete without access fulfilment');
$events->duplicate=true;
$result=$service->receiveProviderEvent('fake',['event'=>$event]);
$expect($result['code']==='duplicate_event_ignored','Duplicate contribution must be idempotent');
$events->duplicate=false; $event['type']='payment.refunded'; $event['providerEventId']='contribution-refund';
$service->receiveProviderEvent('fake',['event'=>$event]);
$expect($intents->row['state']==='refunded','Contribution refund must be recorded');
$expect(count($admissions->calls)===$admissionCount&&count($admissions->revocations)===$revocationCount,'Contributions must not alter course access');
require dirname(__DIR__).'/classes/paymentcatalogservice_class_inc.php';
$catalog=new paymentcatalogservice();
$invalid=$catalog->createProduct(['purposeType'=>'contribution','billingPeriod'=>'monthly']);
$expect(!$invalid['ok'],'Recurring contributions are outside this product contract');
$rows=json_decode(file_get_contents(dirname(__DIR__).'/resources/contributions-zar.json'),true);
$expect(array_column($rows,'totalMinor')===[5750,11500,28750,57500,115000],'Requested VAT-inclusive totals must be exact');
echo "PASS: contribution success, duplicates, refund, no access grants and catalogue totals\n";
