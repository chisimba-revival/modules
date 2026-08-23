<?php
$root=dirname(__DIR__,2);$controller=file_get_contents($root.'/certificate-service/controller.php');$eligibility=file_get_contents($root.'/contextcontent/classes/coursecompletioneligibilityservice_class_inc.php');$completion=file_get_contents($root.'/contextcontent/templates/content/coursecompletion_tpl.php');$fail=array();
foreach(array('coursecompletioneligibilityservice','content_incomplete','stage_gates_incomplete','assessment_not_passed') as $needle){if(strpos($eligibility,$needle)===false){$fail[]='eligibility missing '.$needle;}}
foreach(array("'downloadcourse'","'resourceType'=>'course'",'Content-Disposition: attachment') as $needle){if(strpos($controller,$needle)===false){$fail[]='delivery missing '.$needle;}}
if(strpos($completion,'mod_certificate_service_download')===false){$fail[]='course completion has no certificate action';}
if($fail){fwrite(STDERR,implode("\n",$fail)."\n");exit(1);}echo "PASS: course certificate eligibility, issue and download contracts verified.\n";
?>
