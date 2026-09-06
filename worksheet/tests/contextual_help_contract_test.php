<?php
$root = dirname(__DIR__);
$provider = file_get_contents($root.'/classes/helpcontent_class_inc.php');
$template = file_get_contents($root.'/templates/content/viewstudentworksheet_tpl.php');
$register = file_get_contents($root.'/register.conf');
$renderer = file_get_contents(dirname(dirname($root)).'/framework/app/core_modules/help/classes/contextualhelp_class_inc.php');
$checks = array(
    'submission review exposes contextual Help' => str_contains($template, "->show('worksheet', 'ai-assisted-marking')"),
    'topic uses the marking page lecturer permission' => str_contains($provider, 'isContextLecturer($this->user->userId(), $contextCode)'),
    'student answer pages do not expose marking Help' => substr_count($template, 'ai-assisted-marking') === 1,
    'AI draft remains author controlled' => str_contains($register, 'They do not change the saved result until the [-author-] reviews and saves'),
    'role tokens avoid encoded possessives' => !str_contains($register, "[-readonly-]'s") && !str_contains($register, 'A [-author-]'),
    'manual marking remains available' => str_contains($register, 'You can mark the Worksheet by hand instead'),
    'all new content belongs to the language system' => substr_count($register, 'TEXT: mod_worksheet_help_ai_') >= 16,
    'full guide stays beside the marking task' => str_contains($renderer, 'chisimba-contextual-help-drawer') && !str_contains($renderer, "'action' => 'topic'"),
);
$failed = false;
foreach ($checks as $name => $ok) {
    echo ($ok ? 'PASS: ' : 'FAIL: ').$name.PHP_EOL;
    $failed = $failed || !$ok;
}
exit($failed ? 1 : 0);
