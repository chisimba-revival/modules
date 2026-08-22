<?php
$path = getenv('INGESTSERVICE_REAL_ODT_FIXTURE');
if (!$path || !is_file($path)) { echo "SKIP: set INGESTSERVICE_REAL_ODT_FIXTURE to a LibreOffice ODT fixture\n"; exit(0); }
class ChisimbaObject {}
$GLOBALS['kewl_entry_point_run'] = true;
require dirname(__DIR__) . '/classes/odtingestparser_class_inc.php';
$result = (new odtingestparser())->parse($path);
$errors = array_filter($result['issues'], fn($issue) => $issue['severity'] === 'error');
$imageBlocks = array_filter($result['blocks'], fn($block) => $block['type'] === 'image');
$tableBlocks = array_filter($result['blocks'], fn($block) => $block['type'] === 'table');
$referencedAssets = array();
foreach ($imageBlocks as $block) { $referencedAssets[] = $block['assetId']; }
foreach ($tableBlocks as $block) { $referencedAssets = array_merge($referencedAssets, $block['assets'] ?? array()); }
$referencedAssets = array_unique($referencedAssets);
$checks = array(
    $result['schema'] === 'chisimba.ingest-document/v1',
    empty($errors),
    count($result['blocks']) > 0,
    count($result['assets']) === count($referencedAssets),
    count($tableBlocks) > 0,
    !array_filter($imageBlocks, fn($block) => trim((string) ($block['caption'] ?? '')) === '')
);
if (in_array(false, $checks, true)) { fwrite(STDERR, "FAIL: real LibreOffice ODT acceptance\n"); exit(1); }
echo "OK: real LibreOffice ODT acceptance (" . count($result['blocks']) . " blocks, "
    . count($result['assets']) . " assets)\n";
