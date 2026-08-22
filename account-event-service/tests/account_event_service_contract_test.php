<?php
$root = dirname(__DIR__);
$read = static fn($path) => file_get_contents($root . '/' . $path);
$service = $read('classes/accounteventservice_class_inc.php');
$schema = $read('sql/tbl_account_event_service_events.sql');
$registration = $read('register.conf');
$checks = array(
    'service identity' => str_contains($registration, 'MODULE_ID: account-event-service'),
    'owned table' => str_contains($registration, 'TABLE: tbl_account_event_service_events'),
    'append-only API' => str_contains($service, 'public function append(')
        && !str_contains($service, 'public function update')
        && !str_contains($service, 'public function delete'),
    'secret rejection' => str_contains($service, 'unsafe_metadata')
        && str_contains($service, 'containsSecretKey'),
    'bounded reads' => str_contains($service, "'max_range' => 500"),
    'primary key' => str_contains($schema, "'primary' => TRUE"),
    'subject index' => str_contains($schema, "'account_event_subject'"),
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
echo 'OK: ' . count($checks) . " account event service contract checks\n";
?>
