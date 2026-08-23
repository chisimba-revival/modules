<?php
$this->setLayoutTemplate('mcqtests_layout_tpl.php');
$e = fn($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$u = fn($params) => html_entity_decode($this->uri($params, 'mcqtests'), ENT_QUOTES | ENT_HTML5, 'UTF-8');
$t = fn($key, $fallback) => $this->objLanguage->languageText($key, 'mcqtests', $fallback);
?>
<section class="chisimba-card mcq-ai-chapter-setup">
<h1><?php echo $e($t('mod_mcqtests_ai_chapter_heading', 'Generate quizzes from course chapters')); ?></h1>
<p><?php echo $e($t('mod_mcqtests_ai_chapter_help', 'Select the chapters to use. Each selected chapter will produce an inactive formative test with five grounded questions and will be linked to that chapter automatically.')); ?></p>
<?php if (!empty($chapterQuizError)): ?><div class="error" role="alert"><?php echo $e($t('mod_mcqtests_ai_chapter_generation_failed', 'No chapter quizzes could be generated. Check the selected chapters and AI service, then try again.')); ?></div><?php endif; ?>
<form method="post" action="<?php echo $e($u(array('action' => 'aigeneratechapterquizzes'))); ?>">
<input type="hidden" name="csrf_token" value="<?php echo $e($chapterQuizToken); ?>" />
<div class="mcq-ai-chapter-list">
<?php foreach ($chapterQuizCandidates as $chapter): ?>
<label class="chisimba-card mcq-ai-chapter-option">
<input type="checkbox" name="chapters[]" value="<?php echo $e($chapter['chapterId']); ?>" <?php echo $chapter['eligible'] ? 'checked="checked"' : 'disabled="disabled"'; ?> />
<span><strong><?php echo $e($chapter['title']); ?></strong><br /><?php
if ($chapter['existingTestId'] !== '') { echo $e($t('mod_mcqtests_ai_chapter_existing', 'A chapter quiz is already attached')); }
elseif ($chapter['eligible']) { echo $e($t('mod_mcqtests_ai_chapter_summary', 'Five questions · 70% pass mark')); }
else { echo $e($t('mod_mcqtests_ai_chapter_short', 'There is not enough chapter content to generate a grounded quiz')); }
?></span>
</label>
<?php endforeach; ?>
</div>
<div class="chisimba-form-actions"><button class="button" type="submit"><?php echo $e($t('mod_mcqtests_ai_chapter_generate', 'Generate quiz previews')); ?></button><a class="button chisimba-button-secondary" href="<?php echo $e($u(array('action' => 'newhome'))); ?>"><?php echo $e($this->objLanguage->languageText('word_cancel', 'system', 'Cancel')); ?></a></div>
</form>
</section>
