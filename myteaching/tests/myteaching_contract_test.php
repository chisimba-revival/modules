<?php
/** My Teaching audience and management contract. */
$r=dirname(__DIR__);$reg=file_get_contents($r.'/register.conf');$controller=file_get_contents($r.'/controller.php');$overview=file_get_contents($r.'/classes/teachingcourseoverview_class_inc.php');$view=file_get_contents($r.'/templates/content/main_tpl.php');
$checks=array(
 'dedicated dashboard'=>str_contains($reg,'MODULE_ID: myteaching')&&str_contains($reg,'MODULE_NAME: My Teaching'),
 'course-author scope'=>str_contains($controller,'getContextWhereLecturer(')&&str_contains($overview,'getContextWhereLecturer($userId)')&&!str_contains($overview,'getUserContext('),
 'explicit administrator management'=>str_contains($controller,"\$action==='manage'")&&str_contains($reg,'admin_common|manage')&&str_contains($view,'Managing the My Teaching page'),
 'separate block layout'=>substr_count($controller,"getContextBlocks('myteaching'")===1&&str_contains($controller,"addBlock(\$blockId,\$side,'myteaching'")&&str_contains($view,"theModule = 'myteaching'"),
 'course entry retains management intent'=>str_contains($overview,"'contextaction'=>'controlpanel'")&&str_contains($overview,'Manage course'),
 'no learner obligations'=>!str_contains($controller,'studentdueitems')&&!str_contains($overview,'getStudentResult'),
);
foreach($checks as $label=>$ok){if(!$ok){fwrite(STDERR,"FAIL: $label\n");exit(1);}}echo "PASS: My Teaching is author-scoped with separate administrator management.\n";
