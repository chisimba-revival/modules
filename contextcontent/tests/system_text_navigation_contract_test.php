<?php
$root = dirname(__DIR__);
$chapter = file_get_contents($root . '/templates/content/viewchapter_tpl.php');
$checks = array(
    'all chapters link resolves system text' => str_contains(
        $chapter,
        "code2Txt(\n    'mod_contextcontent_allchapters'"
    ) && !str_contains(
        $chapter,
        "languageText('mod_contextcontent_allchapters'"
    ),
);
foreach ($checks as $name => $ok) {
    if (!$ok) { fwrite(STDERR, "FAIL: $name\n"); exit(1); }
    echo "PASS: $name\n";
}
?>
