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
    'administrator preview is discoverable' => str_contains(
        $register,
        'PAGE: admin_common'
    ) && str_contains($register, 'mod_mylearning_viewstudentpage'),
    'students and administrators may view' => str_contains($controller, 'mayView')
        && str_contains($controller, 'isAdmin()')
        && str_contains($controller, 'getContextWhereStudent'),
    'learning state comes from the shared overview' => str_contains(
        $controller,
        "getObject('studentlearningoverview', 'context')"
    ),
    'Site Home is an explicit escape route' => str_contains($template, 'siteHomeUrl')
        && str_contains($controller, "uri(null, 'postlogin')"),
);

foreach ($checks as $name => $ok) {
    if (!$ok) {
        fwrite(STDERR, "FAIL: {$name}\n");
        exit(1);
    }
}
fwrite(STDOUT, "PASS: My Learning module contract\n");
