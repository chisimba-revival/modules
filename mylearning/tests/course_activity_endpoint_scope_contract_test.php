<?php
/** Ensure interactive assessment controllers reject root or stale course scope. @author Derek Keats */
$modules = dirname(__DIR__, 2);
$controllers = array(
    'assignment' => 'assignment/controller.php',
    'mcqtests' => 'mcqtests/controller.php',
    'offlineassessment' => 'offlineassessment/controller.php',
    'worksheet' => 'worksheet/controller.php',
);
$failed = false;
foreach ($controllers as $name => $relativePath) {
    $source = file_get_contents($modules . '/' . $relativePath);
    $guard = strpos($source, 'mayUseActiveCourse(');
    $dispatch = strpos($source, 'function dispatch');
    $passed = $guard !== false && $dispatch !== false && $guard > $dispatch
        && str_contains($source, "'courseactivitydenied'");
    echo ($passed ? 'PASS: ' : 'FAIL: ') . $name . ' rejects invalid active course scope' . PHP_EOL;
    $failed = $failed || !$passed;
}
$essay = file_get_contents($modules . '/essay/controller.php');
$essayPassed = str_contains($essay, 'hasActiveLearnerContext()')
    && str_contains($essay, 'topicBelongsToActiveContext');
echo ($essayPassed ? 'PASS: ' : 'FAIL: ') . 'essay retains item-level scope validation' . PHP_EOL;
$failed = $failed || !$essayPassed;
exit($failed ? 1 : 0);
