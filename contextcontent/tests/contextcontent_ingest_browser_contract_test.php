<?php
$root = dirname(__DIR__);
$controller = file_get_contents($root . '/controller.php');
$template = file_get_contents($root . '/templates/content/importdocument_tpl.php');
$emptyCourseTemplate = file_get_contents($root . '/templates/content/nochapters_tpl.php');
$courseLayout = file_get_contents($root . '/templates/layout/layout_firstpage_tpl.php');
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
    'single URL encoding' => str_contains($template, 'html_entity_decode($this->uri(')
        && !str_contains($template, '$e($this->uri('),
    'discoverable import entry points' => substr_count(
        $emptyCourseTemplate . $courseLayout,
        'button chisimba-button-secondary contextcontent-import-document'
    ) === 2,
    'entry URLs encoded once' => str_contains(
        $emptyCourseTemplate,
        'html_entity_decode('
    ) && str_contains($courseLayout, 'html_entity_decode(')
        && !str_contains($emptyCourseTemplate, 'htmlspecialchars($importUrl')
        && !str_contains($courseLayout, 'htmlspecialchars($importUrl'),
    'registered permissions' => str_contains($register, 'importdocument,previewdocumentimport,confirmdocumentimport|iscontextlecturer')
);
foreach ($checks as $name => $passed) {
    if (!$passed) { fwrite(STDERR, "FAIL: {$name}\n"); exit(1); }
}
echo 'OK: ' . count($checks) . " contextcontent browser ingest checks\n";
