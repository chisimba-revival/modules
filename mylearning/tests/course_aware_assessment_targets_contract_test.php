<?php
/** Ensure every assessment provider uses the canonical course-aware launcher. @author Derek Keats */
$modules = dirname(__DIR__, 2);
$providers = array(
    'assignment' => 'assignment/classes/assignmentassessmentprovider_class_inc.php',
    'essay' => 'essay/classes/essayassessmentprovider_class_inc.php',
    'mcqtests' => 'mcqtests/classes/mcqtestsassessmentprovider_class_inc.php',
    'offlineassessment' => 'offlineassessment/classes/offlineassessmentassessmentprovider_class_inc.php',
    'worksheet' => 'worksheet/classes/worksheetassessmentprovider_class_inc.php',
);
$failed = false;
foreach ($providers as $name => $relativePath) {
    $source = file_get_contents($modules . '/' . $relativePath);
    $passed = str_contains($source, "getObject('courseawarelaunchservice'")
        && str_contains($source, '->target(');
    echo ($passed ? 'PASS: ' : 'FAIL: ') . $name . ' uses course-aware launch' . PHP_EOL;
    $failed = $failed || !$passed;
}
exit($failed ? 1 : 0);
