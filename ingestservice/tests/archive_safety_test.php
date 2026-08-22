<?php
if (!class_exists('ZipArchive') || !class_exists('DOMDocument')) { echo "SKIP: ZIP and DOM extensions are required\n"; exit(0); }
class ChisimbaObject {}
$GLOBALS['kewl_entry_point_run'] = true;
require dirname(__DIR__) . '/classes/docxingestparser_class_inc.php';
require dirname(__DIR__) . '/classes/odtingestparser_class_inc.php';

$make = function ($extension, array $entries) {
    $path = tempnam(sys_get_temp_dir(), 'ingestservice-') . '.' . $extension;
    $zip = new ZipArchive(); $zip->open($path, ZipArchive::CREATE);
    foreach ($entries as $name => $content) { $zip->addFromString($name, $content); }
    $zip->close(); return $path;
};
$docx = $make('docx', array('word/document.xml' => '<document/>', 'extra.xml' => '<extra/>'));
$odt = $make('odt', array('content.xml' => '<document/>', 'extra.xml' => '<extra/>'));
$blocked = 0;
foreach (array(array(new docxingestparser(), $docx), array(new odtingestparser(), $odt)) as $case) {
    try { $case[0]->parse($case[1], array('maxArchiveEntries' => 1)); }
    catch (RuntimeException $error) { if (str_contains($error->getMessage(), 'too many entries')) { $blocked++; } }
}
@unlink($docx); @unlink($odt);
if ($blocked !== 2) { fwrite(STDERR, "FAIL: archive entry limits\n"); exit(1); }
echo "OK: DOCX and ODT archive entry limits\n";
