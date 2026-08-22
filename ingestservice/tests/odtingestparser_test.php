<?php
if (!class_exists('ZipArchive') || !class_exists('DOMDocument')) { echo "SKIP: ZIP and DOM extensions are required\n"; exit(0); }
class ChisimbaObject {}
$GLOBALS['kewl_entry_point_run'] = true;
require dirname(__DIR__) . '/classes/odtingestparser_class_inc.php';

$path = tempnam(sys_get_temp_dir(), 'ingestservice-') . '.odt';
$zip = new ZipArchive();
$zip->open($path, ZipArchive::CREATE);
$zip->addFromString('mimetype', 'application/vnd.oasis.opendocument.text');
$zip->addFromString('styles.xml', '<?xml version="1.0"?><office:document-styles '
    . 'xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0" xmlns:style="urn:oasis:names:tc:opendocument:xmlns:style:1.0">'
    . '<office:styles><style:style style:name="Heading_20_1" style:display-name="Heading 1" style:family="paragraph"/>'
    . '<style:style style:name="Heading_20_2" style:display-name="Heading 2" style:family="paragraph"/>'
    . '<style:style style:name="Heading_20_3" style:display-name="Heading 3" style:family="paragraph"/>'
    . '<style:style style:name="Chapter_20_Overview" style:display-name="Chapter Overview" style:family="paragraph"/>'
    . '</office:styles></office:document-styles>');
$zip->addFromString('Pictures/lorem.png', "\x89PNG\r\n\x1a\nlorem-fixture");
$zip->addFromString('content.xml', '<?xml version="1.0"?><office:document-content '
    . 'xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0" xmlns:text="urn:oasis:names:tc:opendocument:xmlns:text:1.0" '
    . 'xmlns:draw="urn:oasis:names:tc:opendocument:xmlns:drawing:1.0" xmlns:xlink="http://www.w3.org/1999/xlink" '
    . 'xmlns:svg="urn:oasis:names:tc:opendocument:xmlns:svg-compatible:1.0"><office:body><office:text>'
    . '<text:h text:outline-level="1" text:style-name="Heading_20_1">Lorem Chapter</text:h>'
    . '<text:p text:style-name="Chapter_20_Overview">Lorem ipsum dolor sit amet.</text:p>'
    . '<text:h text:outline-level="2" text:style-name="Heading_20_2">Lorem Page</text:h>'
    . '<text:h text:outline-level="3" text:style-name="Heading_20_3">Consectetur</text:h>'
    . '<text:p>Sed do eiusmod tempor incididunt.<draw:frame><svg:title>Lorem figure</svg:title><svg:desc>Decorative lorem diagram</svg:desc>'
    . '<draw:image xlink:href="Pictures/lorem.png"/></draw:frame></text:p>'
    . '</office:text></office:body></office:document-content>');
$zip->close();

$result = (new odtingestparser())->parse($path);
@unlink($path);
$checks = array(
    $result['schema'] === 'chisimba.ingest-document/v1',
    count($result['blocks']) === 6,
    $result['blocks'][0]['type'] === 'heading' && $result['blocks'][0]['level'] === 1,
    $result['blocks'][1]['style'] === 'Chapter Overview',
    $result['blocks'][2]['level'] === 2,
    $result['blocks'][3]['level'] === 3,
    $result['blocks'][5]['type'] === 'image',
    $result['blocks'][5]['alt'] === 'Decorative lorem diagram',
    $result['blocks'][5]['caption'] === 'Lorem figure',
    count($result['assets']) === 1
);
if (in_array(false, $checks, true)) { fwrite(STDERR, "FAIL: ODT neutral block parsing\n"); exit(1); }
echo "OK: ODT Lorem Ipsum parsing and image extraction\n";
