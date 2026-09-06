<?php
$root=dirname(__DIR__);
$controller=file_get_contents($root.'/controller.php');
$overview=file_get_contents($root.'/classes/administrationoverview_class_inc.php');
$metrics=file_get_contents($root.'/classes/sitemetrics_class_inc.php');
$register=file_get_contents($root.'/register.conf');
$checks=array(
 'administrator-only journey'=>str_contains($controller,'isAdmin()')&&str_contains($controller,"'noaccess_tpl.php'"),
 'separate configurable layout'=>str_contains($controller,"getContextBlocks('myadmin'")&&str_contains($controller,'$managing'),
 'site-health summary'=>str_contains($metrics,"countFrom('tbl_users')")&&str_contains($metrics,"countFrom('tbl_context')")&&str_contains($overview,'getActiveUsers()'),
 'presence categories are exclusive and highest-role first'=>str_contains($overview,'if($user->inAdminGroup($userId))')&&str_contains($overview,'elseif(count((array)$contexts->getContextWhereLecturer($userId))')&&str_contains($overview,'elseif(count((array)$contexts->getContextWhereStudent($userId))'),
 'presence has dashboard presentation'=>str_contains($overview,'presence-role-grid')&&str_contains($overview,'presence-scope-summary'),
 'registered dashboard'=>str_contains($register,'MODULE_ID: myadmin')&&str_contains($register,'My Administration'),
);
foreach($checks as $label=>$passed){if(!$passed){fwrite(STDERR,"FAIL: $label\n");exit(1);}}
echo "PASS: My Administration is an administrator-only operational dashboard.\n";
?>
