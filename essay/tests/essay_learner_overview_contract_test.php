<?php
/** Contract checks for the Essay learner overview. @author Derek Keats */
$template=file_get_contents(__DIR__.'/../templates/content/view_essays_tpl.php');$controller=file_get_contents(__DIR__.'/../controller.php');
$recovery=file_get_contents(__DIR__.'/../templates/content/context_required_tpl.php');
$provider=file_get_contents(__DIR__.'/../classes/essayassessmentprovider_class_inc.php');
$checks=array(
'card-based overview'=>str_contains($template,'essay-submission-card'),
'status is explicit'=>str_contains($template,'Ready to submit')&&str_contains($template,'Marked'),
'feedback is inline'=>str_contains($template,'Lecturer feedback'),
'replacement before marking'=>str_contains($template,'Replace submission'),
'duplicate booking icon removed'=>!str_contains($controller,'$icons .= $bookIcon')&&!str_contains($controller,'$icons .= $unbookIcon'),
'essay titles are not booking controls'=>!str_contains($controller,'$objLink->link = $essay[\'topic\']'),
'booking uses explicit actions'=>str_contains($controller,'Book this essay')&&str_contains($controller,'Go to booked essay')&&str_contains($controller,'Release booking'),
'learner mutations require active membership'=>str_contains($controller,'hasActiveLearnerContext()')&&str_contains($controller,'topicBelongsToActiveContext'),
'booking IDs must belong to the selected topic'=>str_contains($controller,'essayBelongsToTopic'),
'known course recovery is explicit'=>str_contains($recovery,'You are not in this course')&&str_contains($recovery,'Enter course'),
'dashboard route uses shared course launcher'=>str_contains($provider,"getObject('courseawarelaunchservice'")
    && str_contains($provider, '->target('),
);
$failed=false;foreach($checks as $name=>$ok){echo($ok?'PASS: ':'FAIL: ').$name.PHP_EOL;$failed=$failed||!$ok;}exit($failed?1:0);
