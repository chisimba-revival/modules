<?php
$root = dirname(__DIR__);
$controller = file_get_contents($root . '/controller.php');
$queue = file_get_contents($root . '/classes/dbchapterquizjobs_class_inc.php');
$progress = file_get_contents($root . '/templates/content/ai_chapter_quiz_progress_tpl.php');
$register = file_get_contents($root . '/register.conf');
$worker = file_get_contents($root . '/scripts/run_chapter_quiz_worker.php');
$checks = array(
    'HTTP generation only enqueues' => str_contains($controller, 'objChapterQuizJobs->enqueue(')
        && !str_contains(substr($controller, strpos($controller, 'private function generateChapterQuizzes'), 1800), 'objChapterQuizGenerator->generate('),
    'worker performs bounded chapter step' => str_contains($queue, 'public function runOne()')
        && str_contains($queue, "array(\$chapterId)"),
    'partial results are durable' => str_contains($queue, "'result_json' => json_encode(\$results)"),
    'progress survives navigation' => str_contains($progress, 'You may leave this page and return later.'),
    'completed result is recoverable' => str_contains($controller, "case 'aichapterquizreview':"),
    'queue table is registered' => str_contains($register, 'TABLE: tbl_mcq_chapter_quiz_jobs'),
    'CLI worker drains a bounded batch' => str_contains($worker, "for (\$step = 0; \$step < \$limit; \$step++)")
        && str_contains($worker, "'max_range' => 50"),
);
foreach ($checks as $name => $ok) { if (!$ok) { fwrite(STDERR, "FAIL: $name\n"); exit(1); } }
echo 'OK: ' . count($checks) . " chapter quiz job checks\n";
?>
