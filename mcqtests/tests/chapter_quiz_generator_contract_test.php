<?php
$root = dirname(__DIR__);
$controller = file_get_contents($root . '/controller.php');
$service = file_get_contents($root . '/classes/chapterquizgenerator_class_inc.php');
$home = file_get_contents($root . '/templates/content/index_tpl.php');
$setup = file_get_contents($root . '/templates/content/ai_chapter_quizzes_tpl.php');
$register = file_get_contents($root . '/register.conf');
$review = file_get_contents($root . '/templates/content/ai_chapter_quiz_review_tpl.php');
$generator = file_get_contents($root . '/classes/mcqaigenerator_class_inc.php');
$checks = array(
    'standard root action button' => str_contains($home, "'action' => 'aichapterquizzes'")
        && str_contains($home, 'mod_mcqtests_ai_chapter_button'),
    'root action follows shared AI availability' => str_contains(
        $home,
        'if (!empty($aiAvailable))'
    ) && str_contains(
        $controller,
        "setVar('aiAvailable', \$this->objMcqAiGenerator->isAvailable())"
    ) && str_contains($generator, 'public function isAvailable()'),
    'root action URL encoded once' => str_contains($home, 'html_entity_decode('),
    'pre-test workflow' => str_contains($controller, "case 'aichapterquizzes':") && str_contains($controller, "case 'aiinsertchapterquizzes':"),
    'chapter title test name' => str_contains($service, "'name' => mb_substr(trim((string) \$chapter['title'])"),
    'grounded generator reused' => str_contains($service, '$this->ai->generate('),
    'questions inserted' => str_contains($service, '$this->ai->insertQuestions('),
    'automatic chapter link' => str_contains($service, 'updateChapterStageGate('),
    'inactive formative default' => str_contains($service, "'status' => 'inactive'") && str_contains($service, "'testtype' => 'Formative'"),
    'lecturer permissions' => str_contains($register, 'aichapterquizzes,aigeneratechapterquizzes,aichapterquizjob,aichapterquizreview,aiinsertchapterquizzes|isContextLecturer'),
    'site administrator access' => str_contains($controller, '$this->objUser->isAdmin() || $this->contextUsers->isContextLecturer()'),
    'visible correct answer' => str_contains($review, 'mod_mcqtests_ai_correct_answer'),
    'durable correction flag' => str_contains($review, 'correction_flags[]')
        && str_contains($generator, "'needsreview' => !empty(\$question['needsCorrection'])"),
    'setup uses grouped choice cards' => str_contains($setup, 'mcq-ai-chapter-fieldset')
        && str_contains($setup, 'mcq-ai-chapter-option__text'),
    'legacy summary entity decoded' => str_contains($setup, "html_entity_decode(\n    \$t('mod_mcqtests_ai_chapter_summary'"),
);
foreach ($checks as $name => $ok) { if (!$ok) { fwrite(STDERR, "FAIL: $name\n"); exit(1); } }
echo 'OK: ' . count($checks) . " chapter quiz generator checks\n";
?>
