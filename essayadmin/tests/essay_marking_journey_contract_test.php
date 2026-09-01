<?php
/** Contract checks for the Essay marking journey. @author Derek Keats */
$controller=file_get_contents(__DIR__.'/../controller.php');$template=file_get_contents(__DIR__.'/../templates/content/upload_tpl.php');
$checks=array(
'no obsolete date helper'=>!str_contains($template,'getDateDifference'),
'mark constrained to 0-100'=>str_contains($template,'min="0" max="100"')&&str_contains($controller,'(float)$mark > 100'),
'returned document optional'=>str_contains($template,'Returned document')&&str_contains($controller,"!empty(\$_FILES['file']['name'])"),
'skin form actions'=>str_contains($template,'chisimba-form-actions')&&str_contains($template,"getObject('iconservice','ui')"),
);
$failed=false;foreach($checks as $name=>$ok){echo($ok?'PASS: ':'FAIL: ').$name.PHP_EOL;$failed=$failed||!$ok;}exit($failed?1:0);
