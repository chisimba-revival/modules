<?php
$tiers=file_get_contents(dirname(__DIR__).'/templates/content/tiers_tpl.php');
$registration=file_get_contents(dirname(__DIR__,2).'/registration-service/controller.php');
$form=file_get_contents(dirname(__DIR__,2).'/registration-service/templates/content/register_tpl.php');
$expect=static function($condition,$message){if(!$condition){fwrite(STDERR,"FAIL: {$message}\n");exit(1);}};
$expect(str_contains($tiers,"'product'=>(string)\$product['code']"),'Paid membership actions must preserve the exact server-owned product.');
$expect(str_contains($tiers,'class="membership-billing-choice"')
    && str_contains($tiers,'type="radio" name="return_to"')
    && str_contains($tiers,'mod_payment_service_continue'),
    'Each paid tier must offer one billing radio group and one continuation action.');
$expect(str_contains($registration,'->purchasable((string) $query[\'product\'])'),'Registration must resolve the selected product from the authoritative catalogue.');
$expect(str_contains($registration,'($product[\'purpose_type\'] ?? \'\') !== \'membership\''),'Registration must reject non-membership purchase summaries.');
$expect(str_contains($form,'registration-purchase-summary'),'The normal registration form must show the preserved membership choice.');
$expect(str_contains($form,'name="selected_product"')
    && str_contains($registration,'purchaseByCode($this->scalarParam(\'selected_product\'))'),
    'The selected product must survive registration independently of the continuation URL and be revalidated server-side.');
$expect(str_contains($form,"'one_off'=>'purchase_one_month'")
    && str_contains(file_get_contents(dirname(__DIR__,2).'/registration-service/register.conf'),'1 Month, no renewal'),
    'Registration must identify a one-month payment as non-renewing.');
$expect(str_contains($form,"'?return_to=' . rawurlencode")||str_contains($form,"'?return_to='.rawurlencode"),'Existing-account sign-in must preserve the selected membership.');
$paymentController=file_get_contents(dirname(__DIR__).'/controller.php');
$registrationService=file_get_contents(dirname(__DIR__,2).'/registration-service/classes/registrationservice_class_inc.php');
$expect(str_contains($registration,'reserveForPayment($queued[\'pendingId\'])')
    && str_contains($paymentController,"case 'pendingbuy'")
    && str_contains($paymentController,"'registration-payment:'")
    && str_contains($registration,"'pending_id'=>\$queued['pendingId']")
    && str_contains($paymentController,"\$pendingId=\$this->param('pending_id')"),
    'A paid registration must enter checkout before requiring email verification.');
$expect(str_contains($registrationService,"'isActive'=>false")
    && str_contains($registrationService,'setActive($pending[\'provisioned_user_id\'],true)'),
    'Payment must use an inactive reserved identity that activates only after verification.');
fwrite(STDOUT,"PASS: pre-registration membership choice contract\n");
?>
