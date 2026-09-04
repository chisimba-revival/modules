<?php
/** Static contract for the dedicated My Learning module. */
$root = dirname(__DIR__);
$register = file_get_contents($root . '/register.conf');
$controller = file_get_contents($root . '/controller.php');
$template = file_get_contents($root . '/templates/content/main_tpl.php');

$checks = array(
    'module is a dedicated learning destination' => str_contains(
        $register,
        'MODULE_ID: mylearning'
    ),
    'administrator management is discoverable' => str_contains(
        $register,
        'PAGE: admin_common'
    ) && str_contains($register, 'admin_common|manage') && str_contains($register, 'mod_mylearning_manage'),
    'personal view is student scoped and management is explicit' => str_contains($controller, 'mayView')
        && str_contains($controller, "\$action === 'manage'")
        && str_contains($controller, 'getContextWhereStudent')
        && !str_contains($controller, "if (\$this->user->isAdmin()) { return true; }"),
    'learning state comes from the shared overview' => str_contains(
        $controller,
        "getObject('studentlearningoverview', 'context')"
    ),
    'membership purchase is a first-class learner journey' => str_contains(
        $controller,
        "getObject('membershipservice','membership-service')->effectiveTier"
    ) && str_contains($template, 'student-membership-summary')
        && str_contains($template, "'purpose'=>'membership'")
        && str_contains($register, 'DEPENDS: payment-service'),
    'standard two-column student hub' => str_contains($template, 'setNumColumns(2)')
        && str_contains($template, "getObject('postloginmenu', 'toolbar')")
        && str_contains($template, 'chisimba-structural-sidebar mylearning-sidebar')
        && str_contains($template, 'mylearning-sidebar')
        && strpos($template, '. $accountMenu')
            < strpos($template, '. $upperBlocks')
        && strpos($template, '. $upperBlocks')
            < strpos($template, '. $upperEditor')
        && strpos($template, '. $upperEditor')
            < strpos($template, '. $editingSwitch')
        && strpos($template, '. $editingSwitch')
            < strpos($template, '. $lowerBlocks'),
    'sidebar supports page-specific blocks' => str_contains(
        $controller,
        "'mylearning', 'left'"
    ) && str_contains($controller, "'mylearning', 'right'")
        && str_contains($controller, "'mylearning', 'middle'")
        && str_contains($template, '$upperBlocks')
        && str_contains($template, '$lowerBlocks')
        && str_contains($template, '$wideBlocks'),
    'administrator can edit sidebar blocks' => str_contains(
        $controller,
        'dispatchBlockAction'
    ) && str_contains($template, 'contextblocks.js')
        && str_contains($template, "theModule = 'mylearning'")
        && str_contains($template, "\$makeEditor('right'")
        && str_contains($template, "\$makeEditor('left'")
        && str_contains($template, "\$makeEditor('middle'"),
);

foreach ($checks as $name => $ok) {
    if (!$ok) {
        fwrite(STDERR, "FAIL: {$name}\n");
        exit(1);
    }
}
fwrite(STDOUT, "PASS: My Learning module contract\n");
