<?php
if (empty($GLOBALS['kewl_entry_point_run'])) die('No direct access');
$e=fn($s)=>htmlspecialchars((string)$s,ENT_QUOTES,'UTF-8'); $t=fn($k)=>$this->text($k);
$url=fn($p)=>html_entity_decode($this->uri($p,'sightsandsounds'),ENT_QUOTES,'UTF-8');
?>
<h1><?= $e($t('title')) ?></h1>
<p><?= $e($t('intro')) ?></p>
<div class="chisimba-form-actions chisimba-form-actions--equal">
<?php if ($ssManager): ?><a class="button" href="<?= $e($url(['action'=>'bank'])) ?>"><?= $e($t('manage')) ?></a><?php endif ?>
<?= $this->getObject('contextualhelp','help')->show('sightsandsounds','practice') ?>
</div>
<?php if ($this->getParam('saved')==='1'): ?><p role="status"><?= $e($t('saved')) ?></p><?php endif ?>
<?php if (!$ssActivities): ?><p><?= $e($t('empty')) ?></p><?php endif ?>
<?php foreach ($ssActivities as $activity): ?>
<section class="chisimba-form-card"><h2><?= $e($activity['title']) ?></h2>
<p><?= nl2br($e($activity['instructions'])) ?></p>
<p><?= (int)$activity['questioncount'] ?> <?= $e($t('questions')) ?> · <?= $e($t('ungraded')) ?></p>
<form method="post" action="<?= $e($url(['action'=>'start'])) ?>" class="chisimba-form-actions">
<?= $this->access->tokenField() ?><input type="hidden" name="id" value="<?= $e($activity['id']) ?>">
<button type="submit"><?= $e($t('start_resume')) ?></button>
</form></section>
<?php endforeach ?>
