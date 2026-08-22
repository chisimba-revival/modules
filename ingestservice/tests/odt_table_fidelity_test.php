<?php
if (!class_exists('ZipArchive') || !class_exists('DOMDocument')) { echo "SKIP: ZIP and DOM extensions are required\n"; exit(0); }
class ChisimbaObject {}
$GLOBALS['kewl_entry_point_run'] = true;
require dirname(__DIR__) . '/classes/odtingestparser_class_inc.php';

$path = tempnam(sys_get_temp_dir(), 'ingest-table-') . '.odt';
$zip = new ZipArchive();
$zip->open($path, ZipArchive::CREATE);
$zip->addFromString('mimetype', 'application/vnd.oasis.opendocument.text');
$zip->addFromString('Pictures/cell.png', "\x89PNG\r\n\x1a\ncell-image");
$zip->addFromString('content.xml', '<?xml version="1.0"?><office:document-content '
    . 'xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0" xmlns:text="urn:oasis:names:tc:opendocument:xmlns:text:1.0" '
    . 'xmlns:style="urn:oasis:names:tc:opendocument:xmlns:style:1.0" xmlns:fo="urn:oasis:names:tc:opendocument:xmlns:xsl-fo-compatible:1.0" xmlns:table="urn:oasis:names:tc:opendocument:xmlns:table:1.0" '
    . 'xmlns:draw="urn:oasis:names:tc:opendocument:xmlns:drawing:1.0" xmlns:xlink="http://www.w3.org/1999/xlink">'
    . '<office:automatic-styles><style:style style:name="Table_Heading" style:display-name="Table Heading" style:family="paragraph"/>'
    . '<style:style style:name="GreenCell" style:family="table-cell"><style:table-cell-properties fo:background-color="#bff1d9"/></style:style></office:automatic-styles>'
    . '<office:body><office:text><table:table><table:table-row>'
    . '<table:table-cell table:style-name="GreenCell" table:number-columns-spanned="2"><text:p text:style-name="Table_Heading">Merged heading</text:p></table:table-cell>'
    . '<table:covered-table-cell/></table:table-row><table:table-row>'
    . '<table:table-cell table:number-rows-spanned="2"><text:p>Image cell<draw:frame><draw:image xlink:href="Pictures/cell.png"/></draw:frame></text:p></table:table-cell>'
    . '<table:table-cell><text:p>Plain cell</text:p></table:table-cell></table:table-row>'
    . '<table:table-row><table:covered-table-cell/><table:table-cell><text:p>Last cell</text:p></table:table-cell></table:table-row>'
    . '</table:table></office:text></office:body></office:document-content>');
$zip->close();

$result = (new odtingestparser())->parse($path);
@unlink($path);
$table = array_values(array_filter($result['blocks'], fn($block) => $block['type'] === 'table'))[0] ?? array();
$checks = array(
    count($table['rows'] ?? array()) === 3,
    count($table['rows'][0] ?? array()) === 1,
    ($table['rows'][0][0]['header'] ?? false) === true,
    ($table['rows'][0][0]['colspan'] ?? 0) === 2,
    ($table['rows'][0][0]['backgroundColor'] ?? '') === '#bff1d9',
    ($table['rows'][1][0]['rowspan'] ?? 0) === 2,
    count($table['rows'][1][0]['content'] ?? array()) === 2,
    ($table['rows'][1][0]['content'][1]['caption'] ?? 'missing') === '',
    count($table['assets'] ?? array()) === 1,
    count($result['assets']) === 1,
    empty(array_filter($result['issues'], fn($issue) => $issue['severity'] === 'error'))
);
if (in_array(false, $checks, true)) { fwrite(STDERR, "FAIL: ODT table fidelity\n"); exit(1); }
echo "OK: ODT table headers, spans and cell images\n";
