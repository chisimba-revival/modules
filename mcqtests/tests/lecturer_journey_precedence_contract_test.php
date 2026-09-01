<?php
/** Ensure a lecturer cannot be diverted into the learner-only MCQ home. */
$controller = file_get_contents(dirname(__DIR__).'/controller.php');
$template = file_get_contents(dirname(__DIR__).'/templates/content/index_tpl.php');
$checks = array(
    'role routing uses learner-only decision' => substr_count($controller, 'if ($this->isLearnerOnly())') >= 4,
    'lecturer authority precedes student membership' => str_contains($controller, "return !\$this->isValid('add')")
        && str_contains($controller, "isContextMember('Students')"),
    'lecturer overview exposes create test' => str_contains($template, "'action' => 'addstep'")
        && str_contains($template, "mod_mcqtests_addtest"),
    'primary actions use current-colour skin icons' => str_contains($template, "getObject('iconservice', 'ui')")
        && str_contains($template, "render('circle-plus'")
        && str_contains($template, "render('sparkles'"),
);
foreach ($checks as $label => $passed) {
    if (!$passed) { fwrite(STDERR, 'FAIL: '.$label.PHP_EOL); exit(1); }
    echo 'PASS: '.$label.PHP_EOL;
}
