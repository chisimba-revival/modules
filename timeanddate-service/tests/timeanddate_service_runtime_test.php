<?php
class timeanddatetestbase {}
class timeanddateconfigstub
{
    public function getValue($name, $module, $default = null)
    {
        $values = array(
            'TIMEANDDATE_TIMEZONE' => 'Africa/Johannesburg',
            'TIMEANDDATE_DATE_FORMAT' => 'j F Y',
            'TIMEANDDATE_TIME_FORMAT' => 'H:i',
            'TIMEANDDATE_DATETIME_FORMAT' => 'j F Y, H:i',
        );
        return $values[$name] ?? $default;
    }
}

$GLOBALS['kewl_entry_point_run'] = true;
$source = file_get_contents(
    dirname(__DIR__) . '/classes/timeanddateservice_class_inc.php'
);
$source = preg_replace('/^<\?php|\?>$/m', '', $source);
$source = str_replace(
    'class timeanddateservice extends ChisimbaObject',
    'class timeanddateservice extends timeanddatetestbase',
    $source
);
eval($source);
$service = new timeanddateservice();
$service->objConfig = new timeanddateconfigstub();

$checks = array(
    'site timezone' => $service->siteTimezone() === 'Africa/Johannesburg',
    'local to UTC' => $service->toStorage(
        $service->parseLocal('2026-08-24 18:30:00')
    ) === '2026-08-24 16:30:00',
    'UTC to local display' => $service->formatDateTime(
        '2026-08-24 16:30:00'
    ) === '24 August 2026, 18:30',
    'invalid date rejected' => $service->parseLocal(
        '2026-02-30 12:00:00'
    ) === null,
    'DST gap rejected' => $service->parseLocal(
        '2026-03-29 02:30:00',
        'Europe/Paris'
    ) === null,
    'numeric offset rejected' => !$service->isValidTimezone('+02:00'),
    'IANA timezone accepted' => $service->isValidTimezone('Pacific/Auckland'),
    'storage round trip' => $service->toStorage(
        $service->parseStorage('2026-12-31 23:59:59')
    ) === '2026-12-31 23:59:59',
);

foreach ($checks as $name => $passed) {
    if (!$passed) {
        fwrite(STDERR, "FAIL: {$name}\n");
        exit(1);
    }
}
echo 'OK: ' . count($checks) . " time and date runtime checks\n";
?>
