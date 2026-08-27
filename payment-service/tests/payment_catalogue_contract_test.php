<?php
$root=dirname(__DIR__);
$catalog=file_get_contents($root.'/classes/paymentcatalogservice_class_inc.php');
$payments=file_get_contents($root.'/classes/paymentservice_class_inc.php');
$products=file_get_contents($root.'/sql/tbl_payment_service_products.sql');
$prices=file_get_contents($root.'/sql/tbl_payment_service_prices.sql');
$controller=file_get_contents($root.'/controller.php');
$returnTemplate=file_get_contents($root.'/templates/content/return_tpl.php');
$productsTemplate=file_get_contents($root.'/templates/content/products_tpl.php');
$productDb=file_get_contents($root.'/classes/dbpaymentproducts_class_inc.php');
$register=file_get_contents($root.'/register.conf');
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
$expect(str_contains($catalog,'privateCourseProduct')&&str_contains($catalog,"'current_price'"),'Course admission pages must be able to resolve their current server-owned product and price.');
$expect(str_contains($controller,"\$requested=\$this->param('product')")&&str_contains($controller,"['code']===\$requested"),'A course purchase link must narrow the catalogue to its selected product.');
$expect(str_contains($returnTemplate,'Open course')&&str_contains($returnTemplate,'Refresh payment status'),'The browser return must offer the next human action without treating the return as payment proof.');
$expect(str_contains($returnTemplate,'Go to My Learning')
    && str_contains($returnTemplate,"if(!\$good)"),
    'Successful membership checkout must lead into learning rather than immediately offering another purchase.');
$expect(str_contains($catalog,'productPage')&&str_contains($productDb,'countProducts')&&str_contains($productDb,'LIMIT '),'The administrator catalogue must use bounded server-side pagination.');
$expect(str_contains($productDb,'name LIKE')&&str_contains($productDb,'purpose_type')&&str_contains($productDb,'active=1'),'Product search, purpose and status filters must be applied by the server.');
$expect(str_contains($productsTemplate,'Saved products pages')&&str_contains($productsTemplate,'Apply filters')&&str_contains($productsTemplate,'No products match these filters.'),'The product catalogue must expose accessible search, pagination and empty states.');
$expect(str_contains($productsTemplate,'View payment activity')&&str_contains($register,'mod_payment_service_products|site'),'Product setup must be the discoverable administrator landing page with a route to payment activity.');
$expect(str_contains($catalog,"byPurpose('private_course'")&&str_contains($productsTemplate,'canonical product for this course'),'A private course must have one clearly presented canonical product.');
echo "PASS: versioned payment catalogue and fulfilment contract\n";
?>
