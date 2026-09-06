<?php
$base = dirname(__DIR__);
$provider = file_get_contents($base . '/classes/helpcontent_class_inc.php');
$template = file_get_contents($base . '/templates/content/manage_tpl.php');
$register = file_get_contents($base . '/register.conf');
$checks = array(
    strpos($provider, "can('membership.view')") !== false,
    strpos($provider, "'membership-operations'") !== false,
    strpos($template, "show('membership-service', 'membership-operations')") !== false,
    strpos($register, 'DEPENDS: help') !== false,
    strpos($register, '[-readonly-]') !== false,
    strpos($register, '[-author-]') !== false,
    strpos($register, '[-context-]') !== false,
);
if (in_array(false, $checks, true)) { fwrite(STDERR, "Membership contextual Help contract failed.\n"); exit(1); }
echo "Membership contextual Help contract passed.\n";
?>
