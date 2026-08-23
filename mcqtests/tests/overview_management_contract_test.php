<?php
$root = dirname(__DIR__);
$controller = file_get_contents($root . '/controller.php');
$template = file_get_contents($root . '/templates/content/index_tpl.php');
$register = file_get_contents($root . '/register.conf');
$checks = array(
    'both routes use one canonical overview' => str_contains($controller, 'return $this->newHome($testId);')
        && substr_count($controller, "return 'index_tpl.php';") === 1
        && !file_exists($root . '/templates/content/newindex_tpl.php'),
    'legacy gradebook percentage is absent' => !str_contains($template, "['percentage']")
        && !str_contains($template, 'mod_mcqtests_finalmark'),
    'actual question marks are displayed' => str_contains($controller, "['actualmarks']")
        && str_contains($template, 'data-test-marks'),
    'inline marks use protected AJAX' => str_contains($template, "operation: 'mark_one'")
        && str_contains($controller, "consume('mcqtests_overview_update'")
        && str_contains($controller, 'getResults($testId)'),
    'bulk activation and marks exist' => str_contains($template, 'data-operation="activate_all"')
        && str_contains($template, 'data-operation="marks_all"'),
    'marks are distributed to questions' => str_contains($controller, 'intdiv($target, count($questions))')
        && str_contains($controller, "array('mark' => \$mark)"),
    'sentinel date is hidden' => str_contains($template, "str_starts_with(\$value, '0000-00-00')")
        && str_contains($template, 'mod_mcqtests_no_closing_date'),
    'actions are icon only and accessible' => str_contains($template, 'class="chisimba-icon-button"')
        && str_contains($template, 'aria-label=')
        && !str_contains($template, 'mcq-results-action-label'),
    'missing chapter workflow is explicit' => str_contains($controller, "setVar('chapterQuizMissingCount'")
        && str_contains($template, 'mod_mcqtests_ai_missing_chapter_button'),
    'overview endpoint is lecturer protected' => str_contains($register, 'updateoverview|isContextLecturer'),
    'tests follow course chapter order' => str_contains($controller, "['chapterorder']")
        && str_contains($controller, 'usort($data, function ($left, $right)'),
);
foreach ($checks as $name => $ok) {
    if (!$ok) { fwrite(STDERR, "FAIL: $name\n"); exit(1); }
    echo "PASS: $name\n";
}
?>
