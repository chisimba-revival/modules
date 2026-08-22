<?php
$root = dirname(__DIR__);
$consumer = file_get_contents($root . '/classes/contextcontentingestconsumer_class_inc.php');
$register = file_get_contents($root . '/register.conf');
$checks = array(
    'uses File Manager service' => str_contains($consumer, "getObject('fileapi', 'filemanager')"),
    'stores generated assets' => str_contains($consumer, 'storeContextGeneratedImage'),
    'uses managed URL' => str_contains($consumer, "return \$asset['url']"),
    'does not persist data URI' => !str_contains($consumer, "'data:' ."),
    'returns file references' => str_contains($consumer, "'fileId' => \$result['file']['id']"),
    'declares File Manager dependency' => str_contains($register, 'DEPENDS: filemanager')
);
foreach ($checks as $name => $passed) { if (!$passed) { fwrite(STDERR, "FAIL: $name\n"); exit(1); } }
echo "OK: " . count($checks) . " managed ingest asset checks\n";
