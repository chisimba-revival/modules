<?php
$root = dirname(__DIR__);
$read = static fn($path) => file_get_contents($root . '/' . $path);
$service = $read('classes/legalacceptanceservice_class_inc.php');
$schema = $read('sql/tbl_legal_acceptance_service_acceptances.sql');
$registration = $read('register.conf');
$checks = array(
    'service identity' => str_contains($registration, 'MODULE_ID: legal-acceptance-service'),
    'account event dependency' => str_contains($registration, 'DEPENDS: account-event-service'),
    'immutable API' => str_contains($service, 'recordAcceptance')
        && !str_contains($service, 'public function update')
        && !str_contains($service, 'public function delete'),
    'exact content digest' => str_contains($service, "'/^[a-f0-9]{64}$/'"),
    'pending and user subjects' => str_contains(
        $service,
        "array('user', 'pending_registration')"
    ),
    'idempotent evidence' => str_contains($schema, "'unique' => TRUE")
        && str_contains($service, 'already_recorded'),
    'atomic evidence and audit' => str_contains($service, 'beginTransaction()')
        && str_contains($service, 'rollbackTransaction()')
        && str_contains($service, 'commitTransaction()'),
    'bounded history' => str_contains($service, "'max_range' => 500"),
    'no legacy dependency' => !str_contains($registration . $service, 'userregistration'),
    'runtime database quoting' => !str_contains($service, '_objDB')
        && str_contains($service, 'objEngine->getDbObj()'),
);
foreach ($checks as $name => $passed) {
    if (!$passed) {
        fwrite(STDERR, "FAIL: $name\n");
        exit(1);
    }
}
echo 'OK: ' . count($checks) . " legal acceptance service contract checks\n";
?>
