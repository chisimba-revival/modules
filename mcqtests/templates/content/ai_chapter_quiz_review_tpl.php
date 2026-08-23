<?php
$this->setLayoutTemplate('mcqtests_layout_tpl.php');
$e = fn($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$u = fn($params) => html_entity_decode($this->uri($params, 'mcqtests'), ENT_QUOTES | ENT_HTML5, 'UTF-8');
$t = fn($key, $fallback) => $this->objLanguage->languageText($key, 'mcqtests', $fallback);
?>
<section class="chisimba-card mcq-ai-chapter-review">
<h1><?php echo $e($t('mod_mcqtests_ai_chapter_review', 'Review generated chapter quizzes')); ?></h1>
<p><?php echo $e($t('mod_mcqtests_ai_chapter_review_help', 'Review the questions below. Creating them will add one inactive formative test per chapter and set a 70% chapter pass mark.')); ?></p>
<?php foreach ($chapterQuizGenerated as $chapter): ?>
<article class="chisimba-card"><h2><?php echo $e($chapter['title']); ?></h2><ol>
<?php foreach ($chapter['questions'] as $question): ?><li><strong><?php echo $e($question['stem']); ?></strong><ul><?php foreach ($question['options'] as $index => $option): ?><li<?php echo $index === $question['correctIndex'] ? ' class="correct"' : ''; ?>><?php echo $e($option); ?></li><?php endforeach; ?></ul></li><?php endforeach; ?>
</ol></article>
<?php endforeach; ?>
<form method="post" action="<?php echo $e($u(array('action' => 'aiinsertchapterquizzes'))); ?>"><input type="hidden" name="csrf_token" value="<?php echo $e($chapterQuizToken); ?>" /><div class="chisimba-form-actions"><button class="button" type="submit"><?php echo $e($t('mod_mcqtests_ai_chapter_create', 'Create quizzes and add them to chapters')); ?></button><a class="button chisimba-button-secondary" href="<?php echo $e($u(array('action' => 'aichapterquizzes'))); ?>"><?php echo $e($this->objLanguage->languageText('word_cancel', 'system', 'Cancel')); ?></a></div></form>
</section>
