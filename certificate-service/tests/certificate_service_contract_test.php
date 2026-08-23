<?php
$root = dirname(__DIR__);
$service = file_get_contents($root.'/classes/certificateservice_class_inc.php');
$renderer = file_get_contents($root.'/classes/certificatepdfrenderer_class_inc.php');
$register = file_get_contents($root.'/register.conf');
$failures = array();
foreach (array('tbl_certificate_service_bases','tbl_certificate_service_signers','tbl_certificate_service_assignments','tbl_certificate_service_issuances') as $table) {
    if (strpos($register, 'TABLE: '.$table) === false || !is_file($root.'/sql/'.$table.'.sql')) { $failures[]='missing '.$table; }
}
foreach (array('function createBase','function createSigner','function assign','function issue','function assignmentFor') as $contract) {
    if (strpos($service,$contract)===false) { $failures[]='missing '.$contract; }
}
if (strpos($service,'function storeImageAsset')===false || strpos($renderer,'function placeAsset')===false) { $failures[]='managed logo/signature assets missing'; }
if (strpos($service,"'snapshot_json'")===false || strpos($service,"'completion_reference'")===false) { $failures[]='issuance is not immutable/idempotent'; }
if (strpos($service,"'certificate.issued'")===false) { $failures[]='issuance is not audited'; }
if (strpos($register,'PAGE: admin_shared|||mod_certificate_service_title|site')===false
    || strpos($register,'SIDEMENU: postlogin-3|Site Admin||award|mod_certificate_service_title|site')===false) {
    $failures[]='site administration navigation declarations missing';
}
foreach (array('class certificate_service_pdf_document','/MediaBox [0 0 595.28 841.89]','imagejpeg','CERTIFICATE OF','AWARDED TO','Certificate number:') as $needle) {
    if (strpos($renderer,$needle)===false) { $failures[]='renderer missing '.$needle; }
}
if (strpos($renderer,'arbitrary HTML')===false) { $failures[]='safe fixed-layout contract missing'; }
if ($failures) { fwrite(STDERR, implode("\n",$failures)."\n"); exit(1); }
echo "PASS: certificate service ownership, issuance and A4 renderer contracts verified.\n";
?>
