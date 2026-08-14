<?php
$root = dirname(__DIR__);
$register = file_get_contents($root . '/register.conf');
$base = file_get_contents($root . '/classes/contentblockbase_class_inc.php');
$controller = file_get_contents($root . '/controller.php');
$checks = array(
    'context aware' => str_contains($register, 'CONTEXT_AWARE: 1'),
    'no context dependency' => str_contains($register, 'DEPENDS_CONTEXT: 0') && !preg_match('/^DEPENDS: *context$/m', $register),
    'two block types' => str_contains($controller, "array('hero','information')"),
    'render context guard' => str_contains($base, "=== 'context'") && str_contains($base, 'currentContext()'),
    'safe semantic rendering' => str_contains($base, "'blockType' => 'none'") && str_contains($base, 'washout'),
    'csrf guard' => str_contains($controller, 'hash_equals'),
);
foreach ($checks as $name => $ok) {
    if (!$ok) { fwrite(STDERR, "FAIL: $name\n"); exit(1); }
    echo "PASS: $name\n";
}
