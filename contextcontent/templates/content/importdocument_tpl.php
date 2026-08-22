<?php
$e = static function ($value) { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); };
$t = function ($key) { return $this->objLanguage->languageText($key, 'contextcontent'); };
$state = isset($documentImportState) ? $documentImportState : 'upload';
$preview = isset($documentImportPreview) && is_array($documentImportPreview) ? $documentImportPreview : array();
$stage = isset($documentImportStage) && is_array($documentImportStage) ? $documentImportStage : array();
$result = isset($documentImportResult) && is_array($documentImportResult) ? $documentImportResult : array();
?>
<div class="chisimba-workspace contextcontent-document-import">
<h1><?php echo $e($t('mod_contextcontent_importdocument_heading')); ?></h1>

<?php if ($state === 'upload' || $state === 'error'): ?>
<p><?php echo $e($t('mod_contextcontent_importdocument_help')); ?></p>
<p class="info"><?php echo $e($t('mod_contextcontent_importdocument_metadata_notice')); ?></p>
<?php if ($state === 'error'): ?><div class="error"><?php echo $e($t('mod_contextcontent_importdocument_error')); ?></div><?php endif; ?>
<form method="post" enctype="multipart/form-data" action="<?php echo $e($this->uri(array('action' => 'previewdocumentimport'), 'contextcontent')); ?>">
<input type="hidden" name="csrf_token" value="<?php echo $e($contextContentCsrf); ?>">
<label for="source_document"><?php echo $e($t('mod_contextcontent_importdocument_choose')); ?></label>
<input id="source_document" name="source_document" type="file" accept=".odt,.docx,application/vnd.oasis.opendocument.text,application/vnd.openxmlformats-officedocument.wordprocessingml.document" required>
<div class="chisimba-form-actions"><button class="button" type="submit"><?php echo $e($t('mod_contextcontent_importdocument_preview')); ?></button></div>
</form>

<?php elseif ($state === 'preview'): ?>
<h2><?php echo $e($t('mod_contextcontent_importdocument_preview_heading')); ?></h2>
<p class="info"><?php echo $e($t('mod_contextcontent_importdocument_preview_notice')); ?></p>
<p><strong><?php echo $e($stage['name'] ?? ''); ?></strong></p>
<p><?php echo $e($t('mod_contextcontent_importdocument_assets')); ?>: <?php echo count($preview['assets'] ?? array()); ?></p>
<ol class="contextcontent-import-plan">
<?php foreach (($preview['chapters'] ?? array()) as $chapter): ?>
<li><strong><?php echo $e($chapter['title'] ?? ''); ?></strong>
<span>(<?php echo $e(!empty($chapter['overview']) ? $t('mod_contextcontent_importdocument_overview') : $t('mod_contextcontent_importdocument_nooverview')); ?>)</span>
<div><?php echo $e($t('mod_contextcontent_importdocument_pages')); ?>: <?php echo count($chapter['pages'] ?? array()); ?></div>
<ul><?php foreach (($chapter['pages'] ?? array()) as $page): ?><li><?php echo $e($page['title'] ?? ''); ?></li><?php endforeach; ?></ul>
</li>
<?php endforeach; ?>
</ol>
<?php if (!empty($preview['issues'])): ?>
<h2><?php echo $e($t('mod_contextcontent_importdocument_issues')); ?></h2>
<ul><?php foreach ($preview['issues'] as $issue): ?><li class="issue-<?php echo $e($issue['severity'] ?? 'warning'); ?>"><strong><?php echo $e($issue['code'] ?? ''); ?></strong>: <?php echo $e($issue['message'] ?? ''); ?></li><?php endforeach; ?></ul>
<?php endif; ?>
<div class="chisimba-form-actions">
<?php if (!empty($preview['valid'])): ?><form method="post" action="<?php echo $e($this->uri(array('action' => 'confirmdocumentimport'), 'contextcontent')); ?>">
<input type="hidden" name="csrf_token" value="<?php echo $e($contextContentCsrf); ?>"><input type="hidden" name="stage_token" value="<?php echo $e($stage['token'] ?? ''); ?>">
<button class="button" type="submit"><?php echo $e($t('mod_contextcontent_importdocument_confirm')); ?></button></form><?php endif; ?>
<a class="button chisimba-button-secondary" href="<?php echo $e($this->uri(array('action' => 'importdocument'), 'contextcontent')); ?>"><?php echo $e($t('mod_contextcontent_importdocument_cancel')); ?></a>
</div>

<?php elseif ($state === 'result'): $status = $result['status'] ?? 'failed'; ?>
<div class="<?php echo $status === 'completed' || $status === 'unchanged' ? 'success' : 'error'; ?>">
<?php echo $e($t($status === 'completed' ? 'mod_contextcontent_importdocument_success' : ($status === 'unchanged' ? 'mod_contextcontent_importdocument_unchanged' : 'mod_contextcontent_importdocument_failed'))); ?>
</div>
<p><a href="<?php echo $e($this->uri(array(), 'contextcontent')); ?>"><?php echo $e($t('mod_contextcontent_importdocument_return')); ?></a></p>
<?php endif; ?>
</div>
