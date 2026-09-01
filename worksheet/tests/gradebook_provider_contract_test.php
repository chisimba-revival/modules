<?php
/** Static contract checks for Worksheet results exposed to Gradebook. */
$provider = file_get_contents(dirname(__DIR__).'/classes/worksheetassessmentprovider_class_inc.php');
$gradebook = file_get_contents(dirname(__DIR__, 2).'/gradebook/classes/gradebookfunctions_class_inc.php');

$checks = array(
    'provider implements learner result lookup' => str_contains($provider, 'function getStudentResult'),
    'provider reads completed Worksheet results' => str_contains($provider, 'getWorksheetResult($userId, $activityId)'),
    'unmarked sentinel becomes submitted status' => str_contains($provider, "return array('status'=>'submitted', 'mark_percent'=>null)"),
    'marked totals become bounded percentages' => str_contains($provider, 'max(0.0, min(100.0, $percentage))'),
    'Gradebook excludes explicit course lecturers' => str_contains($gradebook, 'getContextLecturers($contextCode)') && str_contains($gradebook, 'isset($lecturerIds[(string) $studentId])'),
);

$failed = false;
foreach ($checks as $label => $passed) {
    echo ($passed ? 'PASS: ' : 'FAIL: ').$label.PHP_EOL;
    $failed = $failed || !$passed;
}
exit($failed ? 1 : 0);
