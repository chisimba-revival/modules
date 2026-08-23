<?php
$e = fn($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$refreshUrl = html_entity_decode($this->uri(array('action'=>'aimarkingjob', 'id'=>$aiMarkingJob['id'])), ENT_QUOTES | ENT_HTML5, 'UTF-8');
if (in_array($aiMarkingJob['status'], array('queued','running'), true)) {
    $this->appendArrayVar('headerParams', '<meta http-equiv="refresh" content="5;url='.$e($refreshUrl).'" />');
}
?>
<section class="chisimba-card worksheet-ai-progress">
    <h1><?php echo $e($this->objLanguage->languageText('mod_worksheet_ai_progress_heading', 'worksheet', 'Preparing marking suggestions')); ?></h1>
    <?php if ($aiMarkingJob['status'] === 'failed'): ?>
        <div class="error" role="alert"><?php echo $e($this->objLanguage->languageText('mod_worksheet_ai_failed', 'worksheet', 'AI suggestions could not be generated. You can continue marking manually.')); ?></div>
    <?php else: ?>
        <p><?php echo $e($this->objLanguage->languageText('mod_worksheet_ai_waiting', 'worksheet', 'The background worker is reviewing the submission. You may leave this page and return later.')); ?></p>
        <progress></progress>
    <?php endif; ?>
    <div class="chisimba-form-actions"><a class="button chisimba-button-secondary" href="<?php echo $e($refreshUrl); ?>"><?php echo $e($this->objLanguage->languageText('mod_worksheet_ai_refresh', 'worksheet', 'Refresh progress')); ?></a></div>
</section>
