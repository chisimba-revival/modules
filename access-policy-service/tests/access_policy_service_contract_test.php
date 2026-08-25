<?php
$root = dirname(__DIR__);
$service = file_get_contents($root . '/classes/accesspolicyservice_class_inc.php');
$registration = file_get_contents($root . '/register.conf');
$checks = array(
    'service identity' => str_contains($registration, 'MODULE_ID: access-policy-service'),
    'agreed policy vocabulary' => str_contains($service, "array('public', 'free', 'tier_1', 'tier_2', 'private')"),
    'one resolver for course and page' => str_contains($service, "array('course', 'page')")
        && substr_count($service, 'public function resolve(') === 1,
    'public allows anonymous' => str_contains($service, "'public_access'"),
    'free requires canonical account' => str_contains($service, 'findByUserId(')
        && str_contains($service, "'sign_in_required'"),
    'tier inheritance delegated' => str_contains($service, 'tierIncludes(')
        && str_contains($service, 'effectiveTier('),
    'private access explicit' => str_contains($service, "'resource_access'")
        && str_contains($service, "'private_entitlement_required'"),
    'entitlement source of grants' => str_contains($service, 'hasEntitlement('),
    'no role or group access' => !preg_match('/isAdmin|group|role/i', $service),
    'no payment provider coupling' => !preg_match('/payment|yoco|paypal/i', $service),
    'explainable decisions' => str_contains($service, "'requiredPolicy'")
        && str_contains($service, "'effectiveTier'"),
);
foreach ($checks as $name => $passed) {
    if (!$passed) {
        fwrite(STDERR, "FAIL: $name\n");
        exit(1);
    }
}
echo 'OK: ' . count($checks) . " access policy service contract checks\n";
?>
