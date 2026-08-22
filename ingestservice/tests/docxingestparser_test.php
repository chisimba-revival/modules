<?php
if (!class_exists('ZipArchive') || !class_exists('DOMDocument')) {
    echo "SKIP: ZIP and DOM extensions are required\n";
    exit(0);
}
class ChisimbaObject {}
$GLOBALS['kewl_entry_point_run'] = true;
require dirname(__DIR__) . '/classes/docxingestparser_class_inc.php';

$path = tempnam(sys_get_temp_dir(), 'ingestservice-') . '.docx';
$zip = new ZipArchive();
$zip->open($path, ZipArchive::CREATE);
$zip->addFromString('word/styles.xml', '<?xml version="1.0"?><w:styles xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">'
    . '<w:style w:type="paragraph" w:styleId="Heading1"><w:name w:val="Heading 1"/></w:style>'
    . '<w:style w:type="paragraph" w:styleId="Overview"><w:name w:val="Chapter Overview"/></w:style>'
    . '<w:style w:type="paragraph" w:styleId="Heading2"><w:name w:val="Heading 2"/></w:style>'
    . '<w:style w:type="paragraph" w:styleId="Heading3"><w:name w:val="Heading 3"/></w:style></w:styles>');
$zip->addFromString('word/_rels/document.xml.rels', '<?xml version="1.0"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
    . '<Relationship Id="rId1" Target="media/image1.png"/></Relationships>');
$zip->addFromString('word/media/image1.png', "\x89PNG\r\n\x1a\nfixture");
$zip->addFromString('word/document.xml', '<?xml version="1.0"?><w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main" '
    . 'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main"><w:body>'
    . '<w:p><w:pPr><w:pStyle w:val="Heading1"/></w:pPr><w:r><w:t>Chapter A</w:t></w:r></w:p>'
    . '<w:p><w:pPr><w:pStyle w:val="Overview"/></w:pPr><w:r><w:t>Overview A</w:t></w:r></w:p>'
    . '<w:p><w:pPr><w:pStyle w:val="Heading2"/></w:pPr><w:r><w:t>Page A</w:t></w:r></w:p>'
    . '<w:p><w:pPr><w:pStyle w:val="Heading3"/></w:pPr><w:r><w:t>Inside page</w:t></w:r></w:p>'
    . '<w:p><w:r><w:t>Body</w:t></w:r><w:r><w:drawing><a:blip r:embed="rId1"/></w:drawing></w:r></w:p>'
    . '</w:body></w:document>');
$zip->close();

$result = (new docxingestparser())->parse($path);
@unlink($path);
$checks = array(
    count($result['chapters']) === 1,
    $result['chapters'][0]['title'] === 'Chapter A',
    str_contains($result['chapters'][0]['overview'], 'Overview A'),
    $result['chapters'][0]['pages'][0]['title'] === 'Page A',
    str_contains($result['chapters'][0]['pages'][0]['html'], '<h3>Inside page</h3>'),
    str_contains($result['chapters'][0]['pages'][0]['html'], 'ingest-asset://asset-'),
    count($result['assets']) === 1
);
if (in_array(false, $checks, true)) {
    fwrite(STDERR, "FAIL: DOCX semantic mapping\n");
    exit(1);
}
echo "OK: DOCX semantic mapping and image extraction\n";
