<?php
if (empty($GLOBALS['kewl_entry_point_run'])) die('No direct access');
$e=fn($s)=>htmlspecialchars((string)$s,ENT_QUOTES,'UTF-8'); $t=fn($k)=>$this->text($k);
$url=fn($p)=>html_entity_decode($this->uri($p,'sightsandsounds'),ENT_QUOTES,'UTF-8');
$uid=(string)$this->getObject('user','security')->userId();
$latest=$this->getObject('practicecompletions','sightsandsounds')->latest($ssScope,$ssActivity['id'],$uid);
?>
<h1><?= $e($ssActivity['title']) ?></h1>
<section class="chisimba-form-card chisimba-form-card--wide">
<p><?= nl2br($e($ssActivity['instructions'])) ?></p>
<p><?= (int)$ssActivity['questioncount'] ?> <?= $e($t('questions')) ?> · <?= $e($t('ungraded')) ?></p>
<p><?= $e($t('activity_note')) ?></p>
<?php if ($latest): ?><p><?= $e($t('latest_result')) ?>: <?= (int)$latest['correct'] ?>/<?= (int)$latest['total'] ?></p><?php endif ?>
<div class="chisimba-form-actions chisimba-form-actions--equal">
<form method="post" action="<?= $e($url(['action'=>'start'])) ?>"><?= $this->access->tokenField() ?><input type="hidden" name="id" value="<?= $e($ssActivity['id']) ?>"><button type="submit"><?= $e($t('start_resume')) ?></button></form>
<a class="button" href="<?= $e($url(['action'=>'home'])) ?>"><?= $e($t('activities')) ?></a>
<?= $this->getObject('contextualhelp','help')->show('sightsandsounds','practice') ?>
</div></section>
