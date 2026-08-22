<?php
$root = dirname(__DIR__);
$controller = file_get_contents($root . '/controller.php');
$template = file_get_contents($root . '/templates/content/importdocument_tpl.php');
$register = file_get_contents($root . '/register.conf');
$checks = array(
    'three-step actions' => str_contains($controller, "case 'importdocument':")
        && str_contains($controller, "case 'previewdocumentimport':")
        && str_contains($controller, "case 'confirmdocumentimport':"),
    'mutations protected' => str_contains($controller, "'previewdocumentimport', 'confirmdocumentimport'"),
    'consumer-neutral staging' => str_contains($controller, "getObject('ingeststagingservice', 'ingestservice')"),
    'multipart upload' => str_contains($template, 'enctype="multipart/form-data"')
        && str_contains($template, 'accept=".odt,.docx,'),
    'explicit confirmation' => str_contains($template, "action' => 'confirmdocumentimport'")
        && str_contains($template, 'stage_token'),
    'registered permissions' => str_contains($register, 'importdocument,previewdocumentimport,confirmdocumentimport|iscontextlecturer')
);
foreach ($checks as $name => $passed) {
    if (!$passed) { fwrite(STDERR, "FAIL: {$name}\n"); exit(1); }
}
echo 'OK: ' . count($checks) . " contextcontent browser ingest checks\n";
