<?php
/** Contract checks for the Essay learner overview. @author Derek Keats */
$template=file_get_contents(__DIR__.'/../templates/content/view_essays_tpl.php');$controller=file_get_contents(__DIR__.'/../controller.php');
$checks=array(
'card-based overview'=>str_contains($template,'essay-submission-card'),
'status is explicit'=>str_contains($template,'Ready to submit')&&str_contains($template,'Marked'),
'feedback is inline'=>str_contains($template,'Lecturer feedback'),
'replacement before marking'=>str_contains($template,'Replace submission'),
'duplicate booking icon removed'=>!str_contains($controller,'$icons .= $bookIcon')&&!str_contains($controller,'$icons .= $unbookIcon'),
);
$failed=false;foreach($checks as $name=>$ok){echo($ok?'PASS: ':'FAIL: ').$name.PHP_EOL;$failed=$failed||!$ok;}exit($failed?1:0);
