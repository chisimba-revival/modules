<?php
$GLOBALS['kewl_entry_point_run'] = true;
class ChisimbaObject {}
require dirname(__DIR__) . '/classes/ingeststagingservice_class_inc.php';
$source = tempnam(sys_get_temp_dir(), 'ingest-stage-source-');
file_put_contents($source, 'test package');
$service = new ingeststagingservice();
$staged = $service->stageFile($source, 'Example.ODT', 'owner-one');
$resolved = $service->resolve($staged['token'], 'owner-one');
$checks = array(
    $resolved['name'] === 'Example.ODT',
    $resolved['extension'] === 'odt',
    file_get_contents($resolved['path']) === 'test package'
);
try { $service->resolve($staged['token'], 'owner-two'); $checks[] = false; } catch (InvalidArgumentException $error) { $checks[] = true; }
$service->discard($staged['token'], 'owner-one');
$checks[] = !is_file($resolved['path']);
@unlink($source);
if (in_array(false, $checks, true)) { fwrite(STDERR, "FAIL: ingest staging service\n"); exit(1); }
echo "OK: ingest staging service\n";
