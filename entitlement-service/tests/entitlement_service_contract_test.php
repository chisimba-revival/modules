<?php
$root = dirname(__DIR__);
$read = static fn($path) => file_get_contents($root . '/' . $path);
$registration = $read('register.conf');
$service = $read('classes/entitlementservice_class_inc.php');
$grants = $read('sql/tbl_entitlement_service_grants.sql');
$revocations = $read('sql/tbl_entitlement_service_revocations.sql');
$checks = array(
    'service identity' => str_contains($registration, 'MODULE_ID: entitlement-service'),
    'separate immutable ledgers' => str_contains($registration, 'tbl_entitlement_service_grants')
        && str_contains($registration, 'tbl_entitlement_service_revocations'),
    'idempotent grants' => str_contains($grants, "'unique' => TRUE")
        && str_contains($service, 'already_granted'),
    'one revocation per grant' => str_contains($revocations, "'unique' => TRUE")
        && str_contains($service, 'already_revoked'),
    'time aware evaluation' => str_contains($service, 'g.effective_at <=')
        && str_contains($service, 'g.expires_at >'),
    'revocation aware evaluation' => str_contains($service, 'r.id IS NULL'),
    'source provenance' => str_contains($grants, "'source_type'")
        && str_contains($grants, "'source_reference'"),
    'manual grants require reason' => str_contains($service, "=== 'manual'")
        && str_contains($service, '$metadata[\'reason\']'),
    'canonical user validation' => str_contains($service, 'findByUserId('),
    'audited mutations' => str_contains($service, 'entitlement.granted')
        && str_contains($service, 'entitlement.revoked'),
    'bounded listing' => str_contains($service, "'max_range' => 500"),
    'grant and revocation history' => str_contains($service, 'historyForUser(')
        && str_contains($service, 'r.reason_code'),
    'secret metadata rejection' => str_contains($service, 'containsSecretKey(')
        && str_contains($service, "'api_key'"),
    'no enrolment or payment writes' => !str_contains($service, 'contextgroups')
        && !str_contains($service, 'payment'),
    'no legacy registration dependency' => !str_contains(
        $registration . $service,
        'userregistration'
    ),
    'runtime database quoting' => !str_contains($service, '_objDB')
        && str_contains($service, 'objEngine->getDbObj()'),
);
foreach ($checks as $name => $passed) {
    if (!$passed) {
        fwrite(STDERR, "FAIL: $name\n");
        exit(1);
    }
}
echo 'OK: ' . count($checks) . " entitlement service contract checks\n";
?>
