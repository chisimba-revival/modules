<?php
$this->setLayoutTemplate('mcqtests_layout_tpl.php');
$e = fn($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$u = fn($params) => html_entity_decode($this->uri($params, 'mcqtests'), ENT_QUOTES | ENT_HTML5, 'UTF-8');
?>
<section class="chisimba-card mcq-ai-chapter-setup">
<h1><?php echo $e($this->objLanguage->languageText('mod_mcqtests_ai_chapter_heading', 'mcqtests')); ?></h1>
<p><?php echo $e($this->objLanguage->languageText('mod_mcqtests_ai_chapter_help', 'mcqtests')); ?></p>
<?php if (!empty($chapterQuizError)): ?><div class="error" role="alert"><?php echo $e($this->objLanguage->languageText('mod_mcqtests_ai_chapter_generation_failed', 'mcqtests')); ?></div><?php endif; ?>
<form method="post" action="<?php echo $e($u(array('action' => 'aigeneratechapterquizzes'))); ?>">
<input type="hidden" name="csrf_token" value="<?php echo $e($chapterQuizToken); ?>" />
<div class="mcq-ai-chapter-list">
<?php foreach ($chapterQuizCandidates as $chapter): ?>
<label class="chisimba-card mcq-ai-chapter-option">
<input type="checkbox" name="chapters[]" value="<?php echo $e($chapter['chapterId']); ?>" <?php echo $chapter['eligible'] ? 'checked="checked"' : 'disabled="disabled"'; ?> />
<span><strong><?php echo $e($chapter['title']); ?></strong><br /><?php
if ($chapter['existingTestId'] !== '') { echo $e($this->objLanguage->languageText('mod_mcqtests_ai_chapter_existing', 'mcqtests')); }
elseif ($chapter['eligible']) { echo $e($this->objLanguage->languageText('mod_mcqtests_ai_chapter_summary', 'mcqtests')); }
else { echo $e($this->objLanguage->languageText('mod_mcqtests_ai_chapter_short', 'mcqtests')); }
?></span>
</label>
<?php endforeach; ?>
</div>
<div class="chisimba-form-actions"><button class="button" type="submit"><?php echo $e($this->objLanguage->languageText('mod_mcqtests_ai_chapter_generate', 'mcqtests')); ?></button><a class="button chisimba-button-secondary" href="<?php echo $e($u(array('action' => 'newhome'))); ?>"><?php echo $e($this->objLanguage->languageText('word_cancel', 'system')); ?></a></div>
</form>
</section>
