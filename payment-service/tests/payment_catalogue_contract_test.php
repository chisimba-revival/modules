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
$catalogueTemplate=file_get_contents($root.'/templates/content/catalogue_tpl.php');
$tiersTemplate=file_get_contents($root.'/templates/content/tiers_tpl.php');
$layoutTemplate=file_get_contents($root.'/templates/layout/payment_layout.php');
$tierService=file_get_contents($root.'/classes/tierpresentationservice_class_inc.php');
$expect(str_contains($controller,"\$purpose=\$this->param('purpose')")
    && str_contains($catalogueTemplate,'Choose your membership')
    && str_contains($catalogueTemplate,'Review <?=$e($tierLabel'),
    'Membership discovery must lead through tier choice and explicit purchase review.');
$expect(str_contains($returnTemplate,'Open course')&&str_contains($returnTemplate,'Refresh payment status'),'The browser return must offer the next human action without treating the return as payment proof.');
$expect(str_contains($returnTemplate,'Go to My Learning')
    && str_contains($returnTemplate,"if(!\$good)"),
    'Successful membership checkout must lead into learning rather than immediately offering another purchase.');
$expect(str_contains($catalog,'productPage')&&str_contains($productDb,'countProducts')&&str_contains($productDb,'LIMIT '),'The administrator catalogue must use bounded server-side pagination.');
$expect(str_contains($productDb,'name LIKE')&&str_contains($productDb,'purpose_type')&&str_contains($productDb,'active=1'),'Product search, purpose and status filters must be applied by the server.');
$expect(str_contains($productsTemplate,'Saved products pages')&&str_contains($productsTemplate,'Apply filters')&&str_contains($productsTemplate,'No products match these filters.'),'The product catalogue must expose accessible search, pagination and empty states.');
$expect(str_contains($productsTemplate,'View payment activity')&&str_contains($register,'mod_payment_service_products|site'),'Product setup must be the discoverable administrator landing page with a route to payment activity.');
$expect(str_contains($controller,"case 'tiers'")
    && str_contains($tiersTemplate,'Your current tier')
    && str_contains($tiersTemplate,"'free courses':\$label.' courses'")
    && str_contains($tiersTemplate,'Upgrade to <?=$e($label)?>'),
    'Membership comparison must show the current tier and connect course discovery to upgrade actions.');
$expect(str_contains($controller,"array('tiers','yocowebhook','paystackwebhook')")
    && str_contains($tiersTemplate,'Register now for free courses')
    && str_contains($tiersTemplate,"'free courses'"),
    'The membership comparison must be public and offer a sentence-case free-registration journey.');
$expect(str_contains($layoutTemplate,"showBlock('login','security')")
    && str_contains($layoutTemplate,"showBlock('register','security')")
    && str_contains($layoutTemplate,'payment-acquisition-sidebar'),
    'Anonymous membership discovery must reuse the canonical sign-in and registration blocks in its sidebar.');
$expect(str_contains($tiersTemplate,'per month, billed annually')
    && str_contains($tiersTemplate,"['amount_minor'])/12"),
    'Annual membership prices must disclose their effective monthly cost without obscuring the annual charge.');
$expect(str_contains($controller,"'purpose'=>'membership','tier'=>\$code")
    || str_contains($tiersTemplate,"'purpose'=>'membership','tier'=>\$code"),
    'A tier upgrade must preserve its chosen tier while offering every published billing option.');
$expect(str_contains($tiersTemplate,"'return_to'=>\$afterRegistration")
    && str_contains($tiersTemplate,'parse_url($afterRegistration)')
    && str_contains($tiersTemplate,"'purpose'=>'membership','tier'=>\$code"),
    'Registration must retain the exact membership tier the visitor selected.');
$expect(str_contains($tierService,'dbpaymenttiercontent')
    && str_contains($controller,'saveTiers()')
    && str_contains($tiersTemplate,'Edit membership page'),
    'Administrators must be able to edit tier summaries and comparison features without changing access policy.');
$expect(str_contains($catalog,"byPurpose('private_course'")&&str_contains($productsTemplate,'canonical product for this course'),'A private course must have one clearly presented canonical product.');
echo "PASS: versioned payment catalogue and fulfilment contract\n";
?>
