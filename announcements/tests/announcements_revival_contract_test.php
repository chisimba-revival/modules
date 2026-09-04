<?php
/** Verify Announcements no longer requires retired delivery services to open. */
$root = dirname(__DIR__);
$controller = file_get_contents($root . '/controller.php');
$register = file_get_contents($root . '/register.conf');
$form = file_get_contents($root . '/templates/content/addedit_tpl.php');
$checks = array(
    'Feed is not loaded during initialisation' => strpos(
        substr($controller, 0, strpos($controller, 'public function __feed()')),
        "getObject('feeder', 'feed')"
    ) === false,
    'Feed access is optional' => strpos($controller, "checkIfRegistered('feed')") !== false,
    'Legacy Mail is not a dependency' => strpos($register, 'DEPENDS:mail') === false,
    'Legacy email control is absent' => strpos($form, "new radio ('email')") === false,
    'Publishing cannot invoke legacy email' => substr_count($controller, '$email = FALSE;') === 2,
    'Description covers site and context audiences' => strpos($register, 'selected site or [-context-] audiences') !== false,
);
foreach ($checks as $name => $passed) {
    if (!$passed) {
        fwrite(STDERR, "FAIL: {$name}\n");
        exit(1);
    }
}
fwrite(STDOUT, "Announcements revival contract: PASS\n");
