<?php
$root = dirname(__DIR__);
$service = file_get_contents($root . '/classes/membershipservice_class_inc.php');
$periods = file_get_contents($root . '/sql/tbl_membership_service_periods.sql');
$registration = file_get_contents($root . '/register.conf');
$checks = array(
    'service identity' => str_contains($registration, 'MODULE_ID: membership-service'),
    'ordered tiers' => str_contains($service, "'free' => 0")
        && str_contains($service, "'tier_1' => 1")
        && str_contains($service, "'tier_2' => 2"),
    'tier inheritance' => str_contains($service, 'tierIncludes('),
    'private is not a tier' => !str_contains($service, "'private' =>"),
    'period lifecycle' => str_contains($service, "'scheduled' => array('active', 'expired')")
        && str_contains($service, "'grace' => array('active', 'expired')"),
    'provider neutral' => !preg_match('/yoco|paypal/i', $service . $periods),
    'idempotent creation' => str_contains($periods, "'unique' => TRUE")
        && str_contains($service, 'already_created'),
    'entitlement integration' => str_contains($service, "'entitlementType' => 'membership_tier'"),
    'effective tier comes from entitlements' => str_contains($service, 'activeForUser(')
        && str_contains($service, "'membership_tier'"),
    'grace has explicit grant' => str_contains($service, "'membership_grace'")
        && str_contains($service, "':grace'"),
    'audited transitions' => str_contains($service, 'membership.period_created')
        && str_contains($service, 'membership.period_'),
    'canonical user validation' => str_contains($service, 'findByUserId('),
    'bounded period times' => str_contains($service, '$endsAt <= $startsAt'),
    'no role or course groups' => !str_contains($service, 'contextgroups')
        && !str_contains($service, 'security_role'),
);
foreach ($checks as $name => $passed) {
    if (!$passed) {
        fwrite(STDERR, "FAIL: $name\n");
        exit(1);
    }
}
echo 'OK: ' . count($checks) . " membership service contract checks\n";
?>
