<?php
/** Contract checks for the learner Essay submission journey. @author Derek Keats */
$controller=file_get_contents(__DIR__.'/../controller.php');
$template=file_get_contents(__DIR__.'/../templates/content/upload_tpl.php');
$checks=array(
 'direct native file input'=>str_contains($template,'type="file"')&&str_contains($template,'name="essayfile"'),
 'accessible upload label'=>str_contains($template,'for="essay-file"'),
 'skin action primitives'=>str_contains($template,'chisimba-form-actions')&&str_contains($template,"getObject('iconservice','ui')"),
 'booking ownership enforced'=>str_contains($controller,"studentid='")&&str_contains($controller,"context='"),
 'uploaded file is persisted'=>str_contains($controller,"uploadFile('essayfile')")&&str_contains($controller,"'studentfileid' => \$fileId"),
);
$failed=false; foreach($checks as $name=>$ok){echo ($ok?'PASS: ':'FAIL: ').$name.PHP_EOL;$failed=$failed||!$ok;} exit($failed?1:0);
