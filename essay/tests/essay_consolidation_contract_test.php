<?php
/** Contract for the single-module Essay architecture. @author Derek Keats */
$root=dirname(__DIR__);
$controller=file_get_contents($root.'/controller.php');
$register=file_get_contents($root.'/register.conf');
$legacyModule=dirname($root).'/essayadmin';
$checks=array(
 'Essay owns management controller'=>str_contains($controller,'extends essaymanagementbase'),
 'lecturer default is management'=>str_contains($controller,"parent::dispatch(\$action)"),
 'Essay no longer depends on Essay Admin'=>!str_contains($register,'DEPENDS: essayadmin'),
 'permissions live in Essay'=>str_contains($register,'RULE: addtopic,edit,edittopic,savetopic'),
 'Essay Admin module has been removed'=>!is_dir($legacyModule),
);
foreach($checks as $label=>$ok){echo($ok?'PASS: ':'FAIL: ').$label.PHP_EOL;if(!$ok){exit(1);}}
