<?php
/** Static contract checks for optional, worker-backed AI marking. */
$root = dirname(__DIR__);
$marker = file_get_contents($root.'/classes/worksheetaimarker_class_inc.php');
$jobs = file_get_contents($root.'/classes/dbworksheetaimarkingjobs_class_inc.php');
$controller = file_get_contents($root.'/controller.php');
$template = file_get_contents($root.'/templates/content/viewstudentworksheet_tpl.php');
$answers = file_get_contents($root.'/classes/dbworksheetanswers_class_inc.php');
$results = file_get_contents($root.'/classes/dbworksheetresults_class_inc.php');

$checks = array(
    'availability delegates to canonical AI service' => str_contains($marker, "getObject('aiservice', 'ai')") && str_contains($marker, "->isAvailable()"),
    'AI helper does not persist marks' => !str_contains($marker, 'saveMarks(') && !str_contains($marker, 'insertMark('),
    'suggestions are bounded by question maximum' => str_contains($marker, 'min($limits[$answerId]'),
    'page request only enqueues provider work' => str_contains($controller, 'objAiMarkingJobs->enqueue') && !str_contains($controller, 'objAiMarker->suggest('),
    'enqueue action requires POST and CSRF' => str_contains($controller, "consume('worksheet_ai_marking'") && str_contains($controller, "REQUEST_METHOD"),
    'saving final marks requires CSRF' => str_contains($controller, "consume('worksheet_save_marks'") && str_contains($template, "worksheetMarkToken"),
    'worker performs provider work' => str_contains($jobs, "getObject('worksheetaimarker', 'worksheet')->suggest"),
    'transient suggestions are removed after final save' => str_contains($controller, 'deleteForResult(') && str_contains($jobs, 'function deleteForResult'),
    'lecturer must explicitly save suggestions' => str_contains($template, "action'=>'savestudentmark'") && str_contains($template, 'aiSuggestions'),
    'reopening requires lecturer POST and CSRF' => str_contains($controller, "consume('worksheet_reopen_submission'") && str_contains($controller, '__reopenstudentworksheet'),
    'reopening retains answers and resets assessment state' => str_contains($answers, 'function resetMarks') && str_contains($results, 'function reopenSubmission'),
    'resubmission updates the reopened attempt' => str_contains($results, "ORDER BY updated DESC, puid DESC LIMIT 1") && str_contains($results, "'completed' => 'Y'"),
    'unmarked answers use a non-null lecturer sentinel' => str_contains($answers, "UNMARKED_LECTURER_ID = 'pending'") && !str_contains($answers, "'lecturer_id' => ''"),
);
foreach ($checks as $label => $passed) {
    if (!$passed) { fwrite(STDERR, "FAIL: {$label}\n"); exit(1); }
    echo "PASS: {$label}\n";
}
?>
