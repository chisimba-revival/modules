<?php
class ChisimbaObject {}
$GLOBALS['kewl_entry_point_run'] = true;
require dirname(__DIR__) . '/classes/contextcontentingestprofile_class_inc.php';
$document = array(
    'schema' => 'chisimba.ingest-document/v1',
    'source' => array('fingerprint' => str_repeat('a', 64)),
    'assets' => array(array('id' => 'asset-1')),
    'issues' => array(),
    'blocks' => array(
        array('type' => 'paragraph', 'text' => 'Preface', 'html' => 'Preface', 'style' => ''),
        array('type' => 'heading', 'level' => 1, 'text' => 'Chapter A', 'html' => 'Chapter A'),
        array('type' => 'paragraph', 'text' => 'Overview', 'html' => 'Overview', 'style' => 'Chapter Overview'),
        array('type' => 'heading', 'level' => 2, 'text' => 'Page A', 'html' => 'Page A'),
        array('type' => 'heading', 'level' => 3, 'text' => 'Inside', 'html' => 'Inside'),
        array('type' => 'paragraph', 'text' => 'Body', 'html' => 'Body', 'style' => ''),
        array('type' => 'image', 'assetId' => 'asset-1', 'assets' => array('asset-1'), 'alt' => '')
    )
);
$result = (new contextcontentingestprofile())->transform($document);
$checks = array(
    $result['valid'],
    $result['schema'] === 'chisimba.contextcontent-import/v1',
    $result['chapters'][0]['title'] === 'Chapter A',
    str_contains($result['chapters'][0]['overview'], '<p>Overview</p>'),
    $result['chapters'][0]['pages'][0]['title'] === 'Page A',
    str_contains($result['chapters'][0]['pages'][0]['html'], '<h3>Inside</h3>'),
    str_contains($result['chapters'][0]['pages'][0]['html'], 'ingest-asset://asset-1'),
    $result['issues'][0]['code'] === 'structure.content_before_chapter'
);
if (in_array(false, $checks, true)) { fwrite(STDERR, "FAIL: Context Content ingest profile\n"); exit(1); }
echo "OK: Context Content ingest profile\n";
