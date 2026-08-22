<?php
if (!class_exists('ZipArchive') || !class_exists('DOMDocument')) { echo "SKIP: ZIP and DOM extensions are required\n"; exit(0); }
class ChisimbaObject {}
$GLOBALS['kewl_entry_point_run'] = true;
require dirname(__DIR__) . '/classes/docxingestparser_class_inc.php';
require dirname(__DIR__) . '/classes/odtingestparser_class_inc.php';

$docxPath = tempnam(sys_get_temp_dir(), 'ingestservice-') . '.docx';
$docx = new ZipArchive(); $docx->open($docxPath, ZipArchive::CREATE);
$docx->addFromString('word/styles.xml', '<?xml version="1.0"?><w:styles xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">'
    . '<w:style w:type="paragraph" w:styleId="Heading1"><w:name w:val="Heading 1"/></w:style>'
    . '<w:style w:type="paragraph" w:styleId="Overview"><w:name w:val="Chapter Overview"/></w:style>'
    . '<w:style w:type="paragraph" w:styleId="Heading2"><w:name w:val="Heading 2"/></w:style></w:styles>');
$docx->addFromString('word/document.xml', '<?xml version="1.0"?><w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body>'
    . '<w:p><w:pPr><w:pStyle w:val="Heading1"/></w:pPr><w:r><w:t>Lorem Chapter</w:t></w:r></w:p>'
    . '<w:p><w:pPr><w:pStyle w:val="Overview"/></w:pPr><w:r><w:t>Lorem ipsum dolor sit amet.</w:t></w:r></w:p>'
    . '<w:p><w:pPr><w:pStyle w:val="Heading2"/></w:pPr><w:r><w:t>Lorem Page</w:t></w:r></w:p>'
    . '<w:p><w:r><w:t>Sed do eiusmod tempor incididunt.</w:t></w:r></w:p></w:body></w:document>');
$docx->close();

$odtPath = tempnam(sys_get_temp_dir(), 'ingestservice-') . '.odt';
$odt = new ZipArchive(); $odt->open($odtPath, ZipArchive::CREATE);
$odt->addFromString('styles.xml', '<?xml version="1.0"?><office:document-styles xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0" '
    . 'xmlns:style="urn:oasis:names:tc:opendocument:xmlns:style:1.0"><office:styles>'
    . '<style:style style:name="Heading_20_1" style:display-name="Heading 1" style:family="paragraph"/>'
    . '<style:style style:name="Overview" style:display-name="Chapter Overview" style:family="paragraph"/>'
    . '<style:style style:name="Heading_20_2" style:display-name="Heading 2" style:family="paragraph"/>'
    . '</office:styles></office:document-styles>');
$odt->addFromString('content.xml', '<?xml version="1.0"?><office:document-content xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0" '
    . 'xmlns:text="urn:oasis:names:tc:opendocument:xmlns:text:1.0"><office:body><office:text>'
    . '<text:h text:outline-level="1" text:style-name="Heading_20_1">Lorem Chapter</text:h>'
    . '<text:p text:style-name="Overview">Lorem ipsum dolor sit amet.</text:p>'
    . '<text:h text:outline-level="2" text:style-name="Heading_20_2">Lorem Page</text:h>'
    . '<text:p>Sed do eiusmod tempor incididunt.</text:p></office:text></office:body></office:document-content>');
$odt->close();

$docxResult = (new docxingestparser())->parse($docxPath);
$odtResult = (new odtingestparser())->parse($odtPath);
@unlink($docxPath); @unlink($odtPath);
$signature = function ($document) {
    return array_map(fn($block) => array(
        'type' => $block['type'],
        'level' => $block['level'] ?? null,
        'text' => $block['text'] ?? '',
        'style' => $block['style'] ?? ''
    ), $document['blocks']);
};
if ($signature($docxResult) !== $signature($odtResult)) {
    fwrite(STDERR, "FAIL: DOCX and ODT neutral output differs\n"); exit(1);
}
echo "OK: equivalent Lorem Ipsum DOCX and ODT produce equivalent neutral blocks\n";
