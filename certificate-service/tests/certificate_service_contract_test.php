<?php
$root = dirname(__DIR__);
$service = file_get_contents($root.'/classes/certificateservice_class_inc.php');
$renderer = file_get_contents($root.'/classes/certificatepdfrenderer_class_inc.php');
$register = file_get_contents($root.'/register.conf');
$failures = array();
foreach (array('tbl_certificate_service_bases','tbl_certificate_service_signers','tbl_certificate_service_assignments','tbl_certificate_service_issuances') as $table) {
    if (strpos($register, 'TABLE: '.$table) === false || !is_file($root.'/sql/'.$table.'.sql')) { $failures[]='missing '.$table; }
}
foreach (array('function createBase','function updateBase','function archiveBase','function createSigner','function updateSigner','function archiveSigner','function assign','function issue','function assignmentFor','function baseById') as $contract) {
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
foreach(array('DejaVuSans.ttf','DejaVuSans-Bold.ttf','DejaVuSerif.ttf','DejaVuSerif-Bold.ttf','DEJAVU-LICENSE.txt') as $font){if(!is_file($root.'/resources/fonts/'.$font)){$failures[]='bundled font asset missing '.$font;}}
if(strpos($renderer,"dirname(__DIR__).'/resources/fonts/'")===false){$failures[]='renderer does not own its font dependency';}
if (strpos($renderer,'arbitrary HTML')===false) { $failures[]='safe fixed-layout contract missing'; }
$controller=file_get_contents($root.'/controller.php');$template=file_get_contents($root.'/templates/content/manage_tpl.php');
foreach(array("case 'savebase'","case 'savesigner'","'ajax'","'csrfToken'","certificate-base-list","certificate-signer-list","certificate-preview__page","Text and line colour","Border and seal colour") as $needle){if(strpos($controller.$template,$needle)===false){$failures[]='management workspace missing '.$needle;}}
foreach(array("case 'deletebase'","case 'deletesigner'","case 'previewbase'","SAMPLE-CERTIFICATE","chisimba-button-danger","trash-2","file-down","View/download sample") as $needle){if(strpos($controller.$template,$needle)===false){$failures[]='saved item action missing '.$needle;}}
if(strpos($service,"code'=>'in_use'")===false||strpos($service,"'status'=>'inactive'")===false){$failures[]='guarded soft deletion missing';}
if(strpos($template,'html_entity_decode($this->uri($params)')===false||strpos($template,'certificate-reset-colours')===false){$failures[]='safe AJAX URL or no-refresh colour reset missing';}
if(preg_match('~action="<\?php echo \$esc\(\$this->uri~',$template)){ $failures[]='form action is vulnerable to double encoding'; }
if ($failures) { fwrite(STDERR, implode("\n",$failures)."\n"); exit(1); }
echo "PASS: certificate service ownership, issuance and A4 renderer contracts verified.\n";
?>
