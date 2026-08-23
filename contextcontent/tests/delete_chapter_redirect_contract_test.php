<?php
$source = file_get_contents(dirname(__DIR__) . '/controller.php');
$unsafe = "array('message' => 'chapterdeleted', 'chapter' => \$chapter)";
if (str_contains($source, $unsafe)) { fwrite(STDERR, "FAIL: deleted chapter title is routed as an identifier\n"); exit(1); }
if (!str_contains($source, "array('message' => 'chapterdeleted')")) { fwrite(STDERR, "FAIL: safe deletion redirect missing\n"); exit(1); }
echo "OK: chapter deletion uses a safe success redirect\n";
