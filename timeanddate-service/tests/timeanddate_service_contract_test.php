<?php
$root = dirname(__DIR__);
$registration = file_get_contents($root . '/register.conf');
$service = file_get_contents($root . '/classes/timeanddateservice_class_inc.php');
$documentation = file_get_contents($root . '/docs/service-contract.md');
$checks = array(
    'service identity' => str_contains($registration, 'MODULE_ID: timeanddate-service'),
    'stateless module' => !str_contains($registration, 'TABLE:'),
    'configuration dependency' => str_contains($registration, 'DEPENDS: sysconfig'),
    'UTC now contract' => str_contains($service, 'public function nowUtc()'),
    'canonical storage' => str_contains($service, "STORAGE_FORMAT = 'Y-m-d H:i:s'")
        && str_contains($service, 'public function toStorage(')
        && str_contains($service, 'public function parseStorage('),
    'strict local input' => str_contains($service, 'public function parseLocal(')
        && str_contains($service, 'isExactParse'),
    'timezone validation' => str_contains($service, 'public function isValidTimezone('),
    'named timezone policy' => str_contains($service, 'DateTimeZone::listIdentifiers()'),
    'site UTC fallback' => str_contains($service, "DEFAULT_TIMEZONE = 'UTC'")
        && str_contains($service, 'TIMEANDDATE_TIMEZONE'),
    'display boundary' => str_contains($service, 'public function formatDate(')
        && str_contains($service, 'public function formatTime(')
        && str_contains($service, 'public function formatDateTime('),
    'no global mutation' => !str_contains($service, 'date_default_timezone_set'),
    'no legacy utility dependency' => !str_contains($service, "'dateandtime'")
        && !str_contains($service, 'objDateTime'),
    'scope documented' => str_contains($documentation, 'does not implement calendars'),
);
foreach ($checks as $name => $passed) {
    if (!$passed) {
        fwrite(STDERR, "FAIL: {$name}\n");
        exit(1);
    }
}
echo 'OK: ' . count($checks) . " time and date service contract checks\n";
?>
