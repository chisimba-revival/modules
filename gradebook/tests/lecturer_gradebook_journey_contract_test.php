<?php
/** Static journey checks for the lecturer Gradebook and Assessment Sheet. */
$home = file_get_contents(dirname(__DIR__).'/templates/content/main_admin_tpl.php');
$sheet = file_get_contents(dirname(__DIR__).'/templates/content/assessment_sheet_tpl.php');
$controller = file_get_contents(dirname(__DIR__).'/controller.php');
$assessmentResults = file_get_contents(dirname(__DIR__).'/templates/content/assessment_results_tpl.php');

$checks = array(
    'learner summary does not expose duplicate plan date state' => !str_contains($home, "L('opennow')"),
    'Gradebook home does not link into its retired inline learner detail' => !str_contains(substr($home, 0, strpos($home, 'return;')), "'learner_id'"),
    'Gradebook home includes the primary class marks matrix' => str_contains($home, 'gradebook-mark-matrix'),
    'class matrix shows provider-owned percentages' => str_contains($home, "getStudentResult"),
    'class matrix supports percentage and year-mark modes' => str_contains($home, "'display'=>'percentage'") && str_contains($home, "'display'=>'year_mark'"),
    'individual year-mark contributions are points not percentages' => str_contains($home, "\$matrixDisplay === 'percentage' ? '%' : ''"),
    'class matrix uses short assessment names' => str_contains($home, "['short_name']"),
    'Assessment Sheet has persistent Gradebook navigation' => str_contains($sheet, 'mod_gradebook_gradebooknavigation') && !str_contains($sheet, 'mod_gradebook_backtoassessmentplan'),
    'plan rows carry adapters for result lookup' => str_contains($controller, "'adapter' => \$adapter"),
    'assessment drill-down has a dedicated route' => str_contains($controller, "case 'assessmentResults'") && str_contains($home, "action'=>'assessmentResults'"),
    'dedicated drill-down puts selector before results' => strpos($assessmentResults, 'gradebook-assessment-selector') < strpos($assessmentResults, 'chisimba-table-wrap'),
);

$failed = false;
foreach ($checks as $label => $passed) {
    echo ($passed ? 'PASS: ' : 'FAIL: ').$label.PHP_EOL;
    $failed = $failed || !$passed;
}
exit($failed ? 1 : 0);
