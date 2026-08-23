<?php
$root = dirname(__DIR__);
$register = file_get_contents($root . '/register.conf');
$service = file_get_contents($root . '/classes/ingestservice_class_inc.php');
preg_match_all('/^CONFIG:\s*([^|]+)\|/m', $register, $matches);
foreach ($matches[1] as $key) {
    if (strlen($key) > 32) {
        fwrite(STDERR, "FAIL: configuration key exceeds legacy schema: {$key}\n");
        exit(1);
    }
    if (!str_contains($service, "'{$key}'")) {
        fwrite(STDERR, "FAIL: registered configuration key is not consumed: {$key}\n");
        exit(1);
    }
}
echo 'OK: ' . count($matches[1]) . " ingest configuration keys\n";
