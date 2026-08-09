<?php
$root = isset($argv[1]) ? rtrim($argv[1], '/') : dirname(__DIR__);
$active = array(
    'controller.php',
    'classes/contentauthoringservice_class_inc.php',
    'classes/db_contextcontent_order_class_inc.php',
    'classes/db_contextcontent_contextchapter_class_inc.php',
    'templates/content/viewpage_tpl.php',
    'templates/content/listchapters_tpl.php',
    'templates/layout/layout_chapter_tpl.php',
    'templates/layout/layout_firstpage_tpl.php'
);
$failures = array();
foreach ($active as $relative) {
    $text = file_get_contents($root . '/' . $relative);
    if ($text === false) { $failures[] = 'missing ' . $relative; continue; }
    $withoutComments = preg_replace('~/\*.*?\*/|//[^\r\n]*~s', '', $text);
    if ($relative !== 'controller.php' && preg_match('/headerscripts|isbookmarked/', $withoutComments)) {
        $failures[] = 'retired column referenced by active file: ' . $relative;
    }
}
$template = file_get_contents($root . '/templates/content/addeditpage_tpl.php');
if (strpos($template, "new hiddeninput('csrf_token'") === false) {
    $failures[] = 'page form has no CSRF token';
}
if (stripos($template, 'setInterval') !== false || stripos($template, 'autosave') !== false) {
    $failures[] = 'dead autosave remains';
}
if ($failures) {
    foreach ($failures as $failure) { fwrite(STDERR, "FAIL: $failure\n"); }
    exit(1);
}
echo "ContextContent active schema contract: PASS\n";
?>
