<?php
$root = dirname(__DIR__);
$read = fn($path) => file_get_contents($root . '/' . $path);
$checks = array(
    'module identity' => str_contains($read('register.conf'), 'MODULE_ID: ingestservice')
        && str_contains($read('register.conf'), 'MODULE_NAME: Content Ingest Service'),
    'neutral schema' => str_contains($read('classes/docxingestparser_class_inc.php'), 'chisimba.ingest-document/v1'),
    'multiple source adapters' => str_contains($read('classes/ingestservice_class_inc.php'), "array('docx', 'odt')")
        && str_contains($read('classes/odtingestparser_class_inc.php'), 'chisimba.ingest-document/v1'),
    'neutral parser' => !str_contains($read('classes/docxingestparser_class_inc.php'), "'heading 1' => 'chapter'")
        && !str_contains($read('classes/docxingestparser_class_inc.php'), 'contextcontent'),
    'capability API' => str_contains($read('classes/ingestservice_class_inc.php'), 'function applyCapability'),
    'dry run excludes bytes' => str_contains($read('classes/ingestservice_class_inc.php'), "unset(\$asset['content'])"),
    'idempotency key' => str_contains($read('sql/tbl_ingestservice_runs.sql'), "'unique' => TRUE")
        && str_contains($read('sql/tbl_ingestservice_runs.sql'), "'sourcefingerprint' => array()"),
    'consumer separation' => !str_contains($read('classes/docxingestparser_class_inc.php'), 'chapters')
);
foreach ($checks as $name => $passed) {
    if (!$passed) { fwrite(STDERR, "FAIL: $name\n"); exit(1); }
}
echo "OK: " . count($checks) . " ingestservice contract checks\n";
