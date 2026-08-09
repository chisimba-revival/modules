<?php
$root = isset($argv[1]) ? rtrim($argv[1], '/') : dirname(__DIR__);
$failures = array();
function ccCheck($condition, $message) {
    global $failures;
    if (!$condition) { $failures[] = $message; }
}
function ccRead($root, $path) {
    $data = file_get_contents($root . '/' . $path);
    if ($data === false) { throw new RuntimeException('Cannot read ' . $path); }
    return $data;
}

$controller = ccRead($root, 'controller.php');
$registry = ccRead($root, 'classes/contenttyperegistry_class_inc.php');
$service = ccRead($root, 'classes/contentauthoringservice_class_inc.php');
$titles = ccRead($root, 'sql/tbl_contextcontent_titles.sql');
$pages = ccRead($root, 'sql/tbl_contextcontent_pages.sql');
$order = ccRead($root, 'sql/tbl_contextcontent_order.sql');
$register = ccRead($root, 'register.conf');
$picker = ccRead($root, 'templates/content/contenttypepicker_tpl.php');
$chapterForm = ccRead($root, 'templates/content/addeditchapter_tpl.php');
$emptyChapterForm = ccRead($root, 'templates/content/nochapters_tpl.php');

ccCheck(strpos($titles, "'contenttype'") !== false, 'typed content identity column is missing');
ccCheck(strpos($titles, "'providermodule'") !== false && strpos($titles, "'provideritemid'") !== false,
    'provider reference columns are missing');
ccCheck(strpos($pages, "'headerscripts'") === false && strpos($pages, "'scorm'") === false,
    'retired executable-header or SCORM page fields remain');
ccCheck(strpos($order, "'isbookmarked'") === false && strpos($order, "'bookmark'") === false,
    'shared placement bookmarks remain');
ccCheck(strpos($register, 'TABLE: tbl_contextcontent_bookmarks') !== false,
    'per-user bookmark table is not registered');
ccCheck(strpos($registry, "'key' => 'rich_text'") !== false
    && strpos($registry, "'key' => 'short_text'") !== false, 'native page types are incomplete');
ccCheck(strpos($registry, 'extends ChisimbaObject') !== false && strpos($service, 'extends ChisimbaObject') !== false, 'service classes must extend the PHP 8-compatible ChisimbaObject base');
ccCheck(strpos($registry, 'extends object') === false && strpos($service, 'extends object') === false, 'obsolete object base class remains');
ccCheck(strpos($registry, 'preferred_for') !== false && strpos($picker, 'Course format changes') !== false,
    'picker defaults are not format-aware and non-restrictive');
ccCheck(strpos($service, 'beginTransaction()') !== false
    && strpos($service, 'commitTransaction()') !== false
    && strpos($service, 'rollbackTransaction()') !== false, 'native authoring is not transactional');
ccCheck(strpos($controller, "const CSRF_CONTEXT = 'contextcontent_authoring'") !== false,
    'dedicated CSRF context is missing');
ccCheck(strpos($controller, 'private function requireAuthorisedMutation') !== false
    && strpos($controller, '!$this->isPost()') !== false
    && strpos($controller, '$this->csrf->consume') !== false, 'mutation guard is incomplete');
ccCheck(strpos($controller, 'private function requireCourseManager') !== false,
    'authoring-form authorisation is missing');
ccCheck(strpos($chapterForm, "new textinput('chaptertitle')") !== false
    && strpos($controller, "getParam('chaptertitle')") !== false,
    'chapter titles must not collide with the chapter identifier parameter');
ccCheck(strpos($chapterForm, "new textinput('chapter')") === false,
    'chapter title still uses the identifier parameter name');
ccCheck(strpos($emptyChapterForm, "new textinput('chaptertitle')") !== false
    && strpos($emptyChapterForm, "new textinput('chapter')") === false,
    'empty-course chapter form still uses the identifier parameter name');
ccCheck(strpos($controller, "getJavaScriptFile('jquery.livequery.js'") === false,
    'livequery is still loaded');

ccCheck(strpos($picker, 'new link($url)') !== false
    && strpos($picker, '$chooseLink->show()') !== false
    && !preg_match('/html(?:entities|specialchars)\s*\(\s*\$url/', $picker),
    'type-picker links must use the framework link renderer without re-escaping generated URIs');
if ($failures) {
    foreach ($failures as $failure) { fwrite(STDERR, "FAIL: $failure\n"); }
    exit(1);
}
echo "ContextContent rebuild contract: PASS\n";
?>
