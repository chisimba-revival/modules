<?php
/** Static contract checks for the default Worksheet rubric. @author Derek Keats */
$root = dirname(__DIR__);
$definition = file_get_contents($root.'/classes/worksheetdefaultrubric_class_inc.php');
$worker = file_get_contents($root.'/classes/dbworksheetaimarkingjobs_class_inc.php');
$marker = file_get_contents($root.'/classes/worksheetaimarker_class_inc.php');
$service = file_get_contents(dirname($root).'/rubric/classes/rubricservice_class_inc.php');
$checks = array(
    'default has four assessment criteria' => substr_count($definition, "'objective' =>") === 4,
    'default includes completeness' => str_contains($definition, 'Coverage and completeness'),
    'template provisioning is versioned and non-destructive' => str_contains($service, 'ensureRubricTemplate') && str_contains($service, 'Existing data is never overwritten'),
    'worker falls back to default rubric' => str_contains($worker, 'worksheetdefaultrubric') && str_contains($worker, '$defaultRubric'),
    'prompt reserves excellent scores for comprehensive answers' => str_contains($marker, 'Reserve 90-100 percent for comprehensive answers'),
);
foreach ($checks as $label => $passed) {
    if (!$passed) { fwrite(STDERR, "FAIL: {$label}\n"); exit(1); }
    echo "PASS: {$label}\n";
}
?>
