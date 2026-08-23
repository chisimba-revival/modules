<?php
$root = dirname(__DIR__);
$controller = file_get_contents($root . '/controller.php');
$service = file_get_contents($root . '/classes/chapterquizgenerator_class_inc.php');
$home = file_get_contents($root . '/templates/content/newindex_tpl.php');
$register = file_get_contents($root . '/register.conf');
$checks = array(
    'root action button' => str_contains($home, "'action' => 'aichapterquizzes'"),
    'pre-test workflow' => str_contains($controller, "case 'aichapterquizzes':") && str_contains($controller, "case 'aiinsertchapterquizzes':"),
    'chapter title test name' => str_contains($service, "'name' => mb_substr(trim((string) \$chapter['title'])"),
    'grounded generator reused' => str_contains($service, '$this->ai->generate('),
    'questions inserted' => str_contains($service, '$this->ai->insertQuestions('),
    'automatic chapter link' => str_contains($service, 'updateChapterStageGate('),
    'inactive formative default' => str_contains($service, "'status' => 'inactive'") && str_contains($service, "'testtype' => 'Formative'"),
    'lecturer permissions' => str_contains($register, 'aichapterquizzes,aigeneratechapterquizzes,aiinsertchapterquizzes|isContextLecturer'),
);
foreach ($checks as $name => $ok) { if (!$ok) { fwrite(STDERR, "FAIL: $name\n"); exit(1); } }
echo 'OK: ' . count($checks) . " chapter quiz generator checks\n";
?>
