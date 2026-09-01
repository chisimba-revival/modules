<?php
/** Static journey checks for the lecturer Gradebook and Assessment Sheet. */
$home = file_get_contents(dirname(__DIR__).'/templates/content/main_admin_tpl.php');
$sheet = file_get_contents(dirname(__DIR__).'/templates/content/assessment_sheet_tpl.php');
$controller = file_get_contents(dirname(__DIR__).'/controller.php');

$checks = array(
    'learner summary does not expose duplicate plan date state' => !str_contains($home, "L('opennow')"),
    'single assessment opens its result list directly' => str_contains($home, 'count($planRows) === 1'),
    'Gradebook home includes the primary class marks matrix' => str_contains($home, 'gradebook-mark-matrix'),
    'class matrix shows provider-owned percentages' => str_contains($home, "getStudentResult"),
    'class matrix supports percentage and year-mark modes' => str_contains($home, "'display'=>'percentage'") && str_contains($home, "'display'=>'year_mark'"),
    'class matrix uses short assessment names' => str_contains($home, "['short_name']"),
    'Assessment Sheet has persistent Gradebook navigation' => str_contains($sheet, 'mod_gradebook_gradebooknavigation') && !str_contains($sheet, 'mod_gradebook_backtoassessmentplan'),
    'plan rows carry adapters for result lookup' => str_contains($controller, "'adapter' => \$adapter"),
);

$failed = false;
foreach ($checks as $label => $passed) {
    echo ($passed ? 'PASS: ' : 'FAIL: ').$label.PHP_EOL;
    $failed = $failed || !$passed;
}
exit($failed ? 1 : 0);
