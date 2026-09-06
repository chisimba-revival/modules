<?php
$tiers=file_get_contents(dirname(__DIR__).'/templates/content/tiers_tpl.php');
$registration=file_get_contents(dirname(__DIR__,2).'/registration-service/controller.php');
$form=file_get_contents(dirname(__DIR__,2).'/registration-service/templates/content/register_tpl.php');
$expect=static function($condition,$message){if(!$condition){fwrite(STDERR,"FAIL: {$message}\n");exit(1);}};
$expect(str_contains($tiers,"'product'=>(string)\$product['code']"),'Paid membership actions must preserve the exact server-owned product.');
$expect(str_contains($registration,'->purchasable((string) $query[\'product\'])'),'Registration must resolve the selected product from the authoritative catalogue.');
$expect(str_contains($registration,'($product[\'purpose_type\'] ?? \'\') !== \'membership\''),'Registration must reject non-membership purchase summaries.');
$expect(str_contains($form,'registration-purchase-summary'),'The normal registration form must show the preserved membership choice.');
$expect(str_contains($form,"'?return_to=' . rawurlencode")||str_contains($form,"'?return_to='.rawurlencode"),'Existing-account sign-in must preserve the selected membership.');
fwrite(STDOUT,"PASS: pre-registration membership choice contract\n");
?>
