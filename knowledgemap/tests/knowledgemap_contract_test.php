<?php
/**
 * Architectural contract for the first Active Knowledge Map slice.
 *
 * @author Derek Keats
 * @package knowledgemap
 */
$root=dirname(__DIR__);$read=fn($file)=>file_get_contents($root.'/'.$file);
$required=array('controller.php','register.conf','classes/knowledgemapgraphservice_class_inc.php','classes/knowledgemapimportservice_class_inc.php','classes/knowledgemapproviderregistry_class_inc.php','classes/knowledgemapauthorizationservice_class_inc.php','classes/knowledgemapiconcatalogue_class_inc.php','classes/knowmapembedservice_class_inc.php','sql/tbl_knowledgemap_maps.sql','sql/tbl_knowledgemap_nodes.sql','sql/tbl_knowledgemap_relationships.sql','sql/tbl_knowledgemap_access.sql','templates/content/index_tpl.php','templates/content/view_tpl.php','resources/knowledgemap.css','resources/knowledgemap.js');
foreach($required as $file)if(!is_file($root.'/'.$file)){fwrite(STDERR,"FAIL: missing $file\n");exit(1);}
$controller=$read('controller.php');$graph=$read('classes/knowledgemapgraphservice_class_inc.php');$importer=$read('classes/knowledgemapimportservice_class_inc.php');$authorization=$read('classes/knowledgemapauthorizationservice_class_inc.php');$template=$read('templates/content/view_tpl.php');$css=$read('resources/knowledgemap.css');
$checks=array(
    'Derek Keats owns module registration'=>str_contains($read('register.conf'),'MODULE_AUTHORS: Derek Keats'),
    'PHP source has author docblocks'=>!preg_match('/<\?php(?![\s\S]{0,500}@author Derek Keats)/',$controller.$graph.$importer),
    'new services declare PHP 8.5 properties'=>str_contains($importer,'private $graph;')&&str_contains($read('classes/knowledgemapservice_class_inc.php'),'private $authorization;')&&str_contains($controller,'private $csrf;'),
    'personal course and site scopes'=>str_contains($read('classes/knowledgemapscopeservice_class_inc.php'),"'personal'")&&str_contains($read('classes/knowledgemapscopeservice_class_inc.php'),"'context'")&&str_contains($read('classes/knowledgemapscopeservice_class_inc.php'),"'site'"),
    'invited users receive ordered permissions'=>str_contains($authorization,"'view'=>1")&&str_contains($authorization,"'edit'=>2")&&str_contains($authorization,"'manage'=>3")&&str_contains($authorization,'invitedUserId'),
    'course members can view course maps'=>str_contains($authorization,'userContext->isContextMember'),
    'graph relationships remain first class'=>str_contains($graph,'RELATIONSHIP_TYPES')&&!str_contains($graph,'parentId'),
    'containment integrity is validated'=>str_contains($graph,'two containment parents')&&str_contains($graph,'containsCycle'),
    'subgraph scope is explicit'=>str_contains($graph,"'descendants'")&&str_contains($graph,"'whole_map'")&&str_contains($graph,"'node'"),
    'Kenga v3 import is transactional and idempotent'=>str_contains($importer,"kenga_knowledge_document")&&str_contains($importer,'beginTransaction')&&str_contains($importer,'rollbackTransaction')&&str_contains($importer,'bySourceFingerprint'),
    'imports fail atomically on rejected graph rows'=>str_contains($importer,'if(!$this->nodes->addNode')&&str_contains($importer,'if(!$this->relationships->addRelationship'),
    'imports verify persisted graph before commit'=>str_contains($importer,'count($storedNodes)!==count($document[\'nodes\'])')&&str_contains($importer,'array_column($storedNodes,\'id\')'),
    'knowledge tables preserve full Unicode'=>count(array_filter(array('sql/tbl_knowledgemap_maps.sql','sql/tbl_knowledgemap_nodes.sql','sql/tbl_knowledgemap_relationships.sql','sql/tbl_knowledgemap_access.sql'),fn($file)=>str_contains($read($file),'utf8mb4_unicode_ci')))===4,
    'module gateways request full Unicode connections'=>count(array_filter(array('classes/dbknowledgemaps_class_inc.php','classes/dbknowledgemapnodes_class_inc.php','classes/dbknowledgemaprelationships_class_inc.php','classes/dbknowledgemapaccess_class_inc.php'),fn($file)=>str_contains($read($file),'SET NAMES utf8mb4')))===4,
    'source migration preserves provenance'=>str_contains($read('sql/tbl_knowledgemap_maps.sql'),'sourcefingerprint')&&str_contains($read('sql/tbl_knowledgemap_maps.sql'),'sourcemetadata'),
    'provider discovery supports push and pull'=>str_contains($read('classes/knowledgemapproviderregistry_class_inc.php'),"array('push','pull','both')")&&str_contains($read('classes/knowledgemapproviderregistry_class_inc.php'),'KNOWLEDGE_MAP_PROVIDER_SCOPES'),
    'read-only embed syntax is visible'=>str_contains($template,'[knowmap id=')&&str_contains($read('classes/knowmapembedservice_class_inc.php'),'data-knowmap-readonly'),
    'viewer uses shared skin primitives'=>str_contains($template,'chisimba-toolbar')&&str_contains($template,'chisimba-spatial-workspace')&&str_contains($template,'chisimba-icon-button'),
    'toolbar uses compact skin icon controls'=>str_contains($template,'chisimba-icon-button--small')&&str_contains($template,'data-knowmap-action="panel"'),
    'visual typography popover edits node presentation'=>str_contains($template,'data-knowmap-action="style"')&&str_contains($template,'data-knowmap-style="fontFamily"')&&str_contains($template,'data-knowmap-style="fontColor"')&&str_contains($read('resources/knowledgemap.js'),'prototype.bindStyleControls'),
    'visual icon picker uses curated skin icons and node drag handle'=>str_contains($template,'data-knowmap-action="icon"')&&str_contains($template,'data-knowmap-icon="lucide:')&&str_contains($read('classes/knowledgemapiconcatalogue_class_inc.php'),"'Nature'")&&str_contains($read('resources/knowledgemap.js'),'prototype.bindIconControls')&&str_contains($read('resources/knowledgemap.js'),'prototype.iconMarkup'),
    'editor supports direct node title editing'=>str_contains($read('resources/knowledgemap.js'),'.knowmap-node__title')&&str_contains($read('resources/knowledgemap.js'),'contenteditable'),
    'nodes expose dedicated drag and collapse controls'=>str_contains($read('resources/knowledgemap.js'),'knowmap-node__drag')&&str_contains($read('resources/knowledgemap.js'),'knowmap-node__collapse')&&str_contains($read('resources/knowledgemap.js'),'offsetX'),
    'layout reserves room for complete visible subtrees'=>str_contains($read('resources/knowledgemap.js'),'prototype.visibleSpan')&&str_contains($read('resources/knowledgemap.js'),'total * gap / 2'),
    'node icon replaces fallback drag ring'=>str_contains($read('resources/knowledgemap.js'),'knowmap-node__drag--icon')&&str_contains($css,'.knowmap-node__drag--icon'),
    'hover controls insert and delete nodes compactly'=>str_contains($read('resources/knowledgemap.js'),'knowmap-node__quick-add')&&str_contains($read('resources/knowledgemap.js'),'knowmap-node__quick-delete')&&str_contains($css,'.knowmap-node:hover .knowmap-node__quick-actions'),
    'empty canvas supports grab panning'=>str_contains($read('resources/knowledgemap.js'),'prototype.bindCanvas')&&str_contains($read('resources/knowledgemap.js'),'setPointerCapture')&&str_contains($css,'.knowmap-canvas.is-panning'),
    'wheel modifiers pan and zoom predictably'=>str_contains($read('resources/knowledgemap.js'),"addEventListener('wheel'")&&str_contains($read('resources/knowledgemap.js'),'event.ctrlKey')&&str_contains($read('resources/knowledgemap.js'),'event.shiftKey')&&str_contains($read('resources/knowledgemap.js'),'scrollLeft += event.deltaY')&&str_contains($read('resources/knowledgemap.js'),'{passive: false}'),
    'details panel composes the shared drawer'=>str_contains($read('resources/knowledgemap.js'),"classList.add('chisimba-drawer')")&&str_contains($read('resources/knowledgemap.js'),'prototype.togglePanel'),
    'workspace chrome can collapse without trapping controls'=>str_contains($template,'data-knowmap-action="chrome"')&&str_contains($read('resources/knowledgemap.js'),'prototype.toggleChrome')&&str_contains($css,'.knowmap-view.is-chrome-collapsed'),
    'true fullscreen removes Chisimba chrome and exits in place'=>substr_count($template,'data-knowmap-action="fullscreen"')===2&&str_contains($read('resources/knowledgemap.js'),'prototype.toggleFullscreen')&&str_contains($read('resources/knowledgemap.js'),'requestFullscreen')&&str_contains($css,'.knowmap-view:fullscreen'),
    'map workspace fills available height'=>str_contains($css,'height: calc(100vh - 12rem)')&&str_contains($css,'height: 100vh')&&str_contains($css,'.knowmap-view.is-panel-closed'),
    'standalone preview save cannot call a missing endpoint'=>str_contains($read('resources/knowledgemap.js'),"saveUrl === '#'")&&str_contains($read('resources/knowledgemap.js'),'Preview saved locally'),
    'visual editor exposes direct graph actions'=>str_contains($template,'data-knowmap-action="add-child"')&&str_contains($template,'data-knowmap-action="add-sibling"')&&str_contains($template,'data-knowmap-node-form')&&str_contains($read('resources/knowledgemap.js'),'prototype.reparent'),
    'editor save is revision protected'=>str_contains($controller,"'save'")&&str_contains($read('classes/knowledgemapservice_class_inc.php'),'lockAtRevision')&&str_contains($read('classes/dbknowledgemaps_class_inc.php'),'FOR UPDATE'),
    'save state is carried by the compact icon'=>str_contains($template,'data-knowmap-save-status role="status"')&&str_contains($read('resources/knowledgemap.js'),'prototype.setSaveState')&&str_contains($css,'.knowmap-save-control.is-unsaved'),
    'search collapse and links are first-class interactions'=>str_contains($template,'data-knowmap-search')&&str_contains($read('resources/knowledgemap.js'),'prototype.toggleCollapse')&&str_contains($read('resources/knowledgemap.js'),'prototype.setLink'),
    'search composes compact accessible skin field'=>str_contains($template,'chisimba-search-field knowmap-search')&&str_contains($template,'chisimba-visually-hidden'),
    'module CSS composes rather than redefines primitives'=>!preg_match('/^\.button\s*\{/m',$css)&&!preg_match('/^\.chisimba-card\s*\{/m',$css)&&!preg_match('/^\.chisimba-toolbar\s*\{/m',$css),
    'all mutations use native CSRF'=>str_contains($controller,'csrf->consume(self::CSRF')
);
foreach($checks as $name=>$ok){if(!$ok){fwrite(STDERR,"FAIL: $name\n");exit(1);}echo "PASS: $name\n";}
