<?php
$base = dirname(__DIR__);
$provider = file_get_contents($base . '/classes/helpcontent_class_inc.php');
$template = file_get_contents($base . '/templates/content/operations_tpl.php');
$register = file_get_contents($base . '/register.conf');
$checks = array(
    strpos($provider, "can('payment.view')") !== false,
    strpos($provider, "'payment-operations'") !== false,
    strpos($template, "show('payment-service','payment-operations')") !== false,
    strpos($register, 'DEPENDS: help') !== false,
    strpos($register, '[-context-]') !== false,
);
if (in_array(false, $checks, true)) { fwrite(STDERR, "Payment contextual Help contract failed.\n"); exit(1); }
echo "Payment contextual Help contract passed.\n";
?>
