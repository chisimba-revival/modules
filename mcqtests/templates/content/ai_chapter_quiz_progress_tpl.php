<?php
$this->setLayoutTemplate('mcqtests_layout_tpl.php');
$e = fn($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$u = fn($params) => html_entity_decode($this->uri($params, 'mcqtests'), ENT_QUOTES | ENT_HTML5, 'UTF-8');
$t = fn($key, $fallback) => $this->objLanguage->languageText($key, 'mcqtests', $fallback);
$status = (string) ($chapterQuizJob['status'] ?? 'queued');
$done = (int) ($chapterQuizJob['progress_completed'] ?? 0);
$total = max(1, (int) ($chapterQuizJob['progress_total'] ?? 1));
$percent = min(100, (int) floor(($done / $total) * 100));
$refreshUrl = $u(array('action' => 'aichapterquizjob', 'id' => $chapterQuizJob['id']));
if (in_array($status, array('queued', 'running'), true)) {
    $this->appendArrayVar('headerParams', '<meta http-equiv="refresh" content="5;url=' . $e($refreshUrl) . '" />');
}
?>
<section class="chisimba-card mcq-ai-chapter-progress">
    <h1><?php echo $e($t('mod_mcqtests_ai_chapter_progress_heading', 'Generating chapter quizzes')); ?></h1>
    <?php if ($status === 'failed'): ?>
        <div class="error" role="alert"><?php echo $e($t('mod_mcqtests_ai_chapter_job_failed', 'The quiz previews could not be generated.')); ?></div>
    <?php else: ?>
        <p><?php echo $e(sprintf($t('mod_mcqtests_ai_chapter_progress', '%d of %d chapters complete'), $done, $total)); ?></p>
        <progress value="<?php echo $e($done); ?>" max="<?php echo $e($total); ?>"><?php echo $e($percent); ?>%</progress>
        <?php if (!empty($chapterQuizJob['current_chapter'])): ?>
            <p><?php echo $e(sprintf($t('mod_mcqtests_ai_chapter_current', 'Working on: %s'), $chapterQuizJob['current_chapter'])); ?></p>
        <?php else: ?>
            <p><?php echo $e($t('mod_mcqtests_ai_chapter_waiting', 'Waiting for the background worker. You may leave this page and return later.')); ?></p>
        <?php endif; ?>
    <?php endif; ?>
    <div class="chisimba-form-actions">
        <a class="button chisimba-button-secondary" href="<?php echo $e($refreshUrl); ?>"><?php echo $e($t('mod_mcqtests_ai_chapter_refresh', 'Refresh progress')); ?></a>
        <a class="button chisimba-button-secondary" href="<?php echo $e($u(array())); ?>"><?php echo $e($t('mod_mcqtests_ai_chapter_leave', 'Back to MCQ Tests')); ?></a>
    </div>
</section>
