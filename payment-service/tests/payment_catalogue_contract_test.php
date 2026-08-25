<?php
$root=dirname(__DIR__);
$catalog=file_get_contents($root.'/classes/paymentcatalogservice_class_inc.php');
$payments=file_get_contents($root.'/classes/paymentservice_class_inc.php');
$products=file_get_contents($root.'/sql/tbl_payment_service_products.sql');
$prices=file_get_contents($root.'/sql/tbl_payment_service_prices.sql');
$controller=file_get_contents($root.'/controller.php');
$expect=function($ok,$message){if(!$ok)throw new RuntimeException($message);};
$expect(str_contains($prices,"'version_code'")&&str_contains($prices,"'unique' => TRUE"),'Price versions must have durable unique identity.');
$expect(!str_contains($catalog,'$this->prices->update('),'Published price versions must be immutable.');
$expect(str_contains($payments,'createIntentFromProduct')&&str_contains($payments,"'amountMinor'=>\$product['price']['amount_minor']"),'Checkout amount must come from the canonical catalogue.');
$expect(str_contains($payments,'latestCoverageEnd')&&str_contains($payments,"modify('+1 second')"),'Repeat membership payment must extend existing coverage.');
$expect(str_contains($payments,'isAdmitted')&&str_contains($payments,"'already_has_access'"),'Lifetime private-course access must not be sold twice.');
$expect(str_contains($payments,"'payment-intent:'.\$intent['id']"),'Membership fulfilment must be idempotent per intent.');
$expect(str_contains($products,"'private_course'")===false,'Product schema must remain generic rather than adding course-specific columns.');
$expect(str_contains($catalog,"array('tier_1','tier_2')")&&str_contains($catalog,"'private_course_required'"),'Products must reference a supported paid tier or a real private course.');
$expect(str_contains($catalog,"\$period==='one_off'")&&str_contains($catalog,"\$duration=null"),'One-off private-course products must not require a membership duration.');
$expect(str_contains($controller,"class_alias('payment_service','payment-service')"),'The hyphenated module id must resolve to its PHP controller class.');
echo "PASS: versioned payment catalogue and fulfilment contract\n";
?>
