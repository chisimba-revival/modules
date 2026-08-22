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
$docx->addFromString('word/_rels/document.xml.rels', '<?xml version="1.0"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
    . '<Relationship Id="rLink" TargetMode="External" Target="https://example.org/lorem"/></Relationships>');
$docx->addFromString('word/numbering.xml', '<?xml version="1.0"?><w:numbering xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">'
    . '<w:abstractNum w:abstractNumId="0"><w:lvl w:ilvl="0"><w:numFmt w:val="decimal"/></w:lvl></w:abstractNum>'
    . '<w:num w:numId="1"><w:abstractNumId w:val="0"/></w:num></w:numbering>');
$docx->addFromString('word/document.xml', '<?xml version="1.0"?><w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main" '
    . 'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><w:body>'
    . '<w:p><w:pPr><w:pStyle w:val="Heading1"/></w:pPr><w:r><w:t>Lorem Chapter</w:t></w:r></w:p>'
    . '<w:p><w:pPr><w:pStyle w:val="Overview"/></w:pPr><w:r><w:t>Lorem ipsum dolor sit amet.</w:t></w:r></w:p>'
    . '<w:p><w:pPr><w:pStyle w:val="Heading2"/></w:pPr><w:r><w:t>Lorem Page</w:t></w:r></w:p>'
    . '<w:p><w:r><w:t>Sed do </w:t></w:r><w:hyperlink r:id="rLink"><w:r><w:t>eiusmod</w:t></w:r></w:hyperlink>'
    . '<w:r><w:t> tempor incididunt.</w:t></w:r></w:p>'
    . '<w:p><w:pPr><w:numPr><w:ilvl w:val="0"/><w:numId w:val="1"/></w:numPr></w:pPr><w:r><w:t>First item</w:t></w:r></w:p>'
    . '<w:p><w:pPr><w:numPr><w:ilvl w:val="0"/><w:numId w:val="1"/></w:numPr></w:pPr><w:r><w:t>Second item</w:t></w:r></w:p>'
    . '<w:tbl><w:tr><w:tc><w:p><w:r><w:t>Alpha</w:t></w:r></w:p></w:tc><w:tc><w:p><w:r><w:t>Beta</w:t></w:r></w:p></w:tc></w:tr></w:tbl>'
    . '</w:body></w:document>');
$docx->close();

$odtPath = tempnam(sys_get_temp_dir(), 'ingestservice-') . '.odt';
$odt = new ZipArchive(); $odt->open($odtPath, ZipArchive::CREATE);
$odt->addFromString('styles.xml', '<?xml version="1.0"?><office:document-styles xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0" '
    . 'xmlns:style="urn:oasis:names:tc:opendocument:xmlns:style:1.0" xmlns:text="urn:oasis:names:tc:opendocument:xmlns:text:1.0"><office:styles>'
    . '<style:style style:name="Heading_20_1" style:display-name="Heading 1" style:family="paragraph"/>'
    . '<style:style style:name="Overview" style:display-name="Chapter Overview" style:family="paragraph"/>'
    . '<style:style style:name="Heading_20_2" style:display-name="Heading 2" style:family="paragraph"/>'
    . '<text:list-style style:name="Numbering"><text:list-level-style-number text:level="1"/></text:list-style>'
    . '</office:styles></office:document-styles>');
$odt->addFromString('content.xml', '<?xml version="1.0"?><office:document-content xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0" '
    . 'xmlns:text="urn:oasis:names:tc:opendocument:xmlns:text:1.0" xmlns:xlink="http://www.w3.org/1999/xlink" '
    . 'xmlns:table="urn:oasis:names:tc:opendocument:xmlns:table:1.0"><office:body><office:text>'
    . '<text:h text:outline-level="1" text:style-name="Heading_20_1">Lorem Chapter</text:h>'
    . '<text:p text:style-name="Overview">Lorem ipsum dolor sit amet.</text:p>'
    . '<text:h text:outline-level="2" text:style-name="Heading_20_2">Lorem Page</text:h>'
    . '<text:p>Sed do <text:a xlink:href="https://example.org/lorem">eiusmod</text:a> tempor incididunt.</text:p>'
    . '<text:list text:style-name="Numbering"><text:list-item><text:p>First item</text:p></text:list-item>'
    . '<text:list-item><text:p>Second item</text:p></text:list-item></text:list>'
    . '<table:table><table:table-row><table:table-cell><text:p>Alpha</text:p></table:table-cell>'
    . '<table:table-cell><text:p>Beta</text:p></table:table-cell></table:table-row></table:table>'
    . '</office:text></office:body></office:document-content>');
$odt->close();

$docxResult = (new docxingestparser())->parse($docxPath);
$odtResult = (new odtingestparser())->parse($odtPath);
@unlink($docxPath); @unlink($odtPath);
$signature = function ($document) {
    return array_map(fn($block) => array(
        'type' => $block['type'],
        'level' => $block['level'] ?? null,
        'text' => $block['text'] ?? '',
        'style' => $block['style'] ?? '',
        'html' => $block['html'] ?? '',
        'ordered' => $block['ordered'] ?? null,
        'items' => array_map(fn($item) => array($item['text'], $item['html']), $block['items'] ?? array()),
        'rows' => array_map(fn($row) => array_map(fn($cell) => array($cell['text'], $cell['html']), $row), $block['rows'] ?? array())
    ), $document['blocks']);
};
if ($signature($docxResult) !== $signature($odtResult)) {
    fwrite(STDERR, "FAIL: DOCX and ODT neutral output differs\n"); exit(1);
}
echo "OK: equivalent Lorem Ipsum DOCX and ODT produce equivalent neutral blocks\n";
