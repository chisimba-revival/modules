<?php
if (empty($GLOBALS['kewl_entry_point_run'])) die('No direct access');
$e=fn($s)=>htmlspecialchars((string)$s,ENT_QUOTES,'UTF-8'); $t=fn($k)=>$this->text($k);
$url=fn($p)=>html_entity_decode($this->uri($p,'sightsandsounds'),ENT_QUOTES,'UTF-8');
?>
<h1><?= $e($t('publish')) ?></h1>
<div class="chisimba-form-actions chisimba-form-actions--equal"><a class="button" href="<?= $e($url(['action'=>'home'])) ?>"><?= $e($t('activities')) ?></a>
<?= $this->getObject('contextualhelp','help')->show('sightsandsounds','authoring') ?></div>
<?php if ($this->getVar('ssError')): ?><p role="alert"><?= $e($this->getVar('ssError')) ?></p><?php endif ?>
<?php if ($this->getParam('saved')==='1'): ?><p role="status"><?= $e($t('saved')) ?></p><?php endif ?>
<form method="post" action="<?= $e($url(['action'=>'publish'])) ?>" class="chisimba-form-card chisimba-form-card--wide">
<h2><?= $e($t('publish')) ?></h2><p><?= $e($t('publish_note')) ?></p><?= $this->access->tokenField() ?>
<?php foreach (['title'=>200,'instructions'=>2000] as $name=>$max): ?>
<div class="chisimba-form-field chisimba-form-field--brief"><label for="ss-<?= $e($name) ?>"><?= $e($t('field_'.$name)) ?></label>
<textarea id="ss-<?= $e($name) ?>" name="<?= $e($name) ?>" maxlength="<?= $max ?>" rows="2" <?= $name==='title'?'required':'' ?>><?= $e($ssInput[$name]??'') ?></textarea></div>
<?php endforeach ?>
<div class="chisimba-form-field"><label for="ss-count"><?= $e($t('count')) ?></label><input type="number" id="ss-count" name="questioncount" min="1" max="<?= max(1,min(50,count($ssItems))) ?>" value="<?= $e(($ssInput['questioncount']??'')?:min(10,max(1,count($ssItems)))) ?>" required></div>
<p><?= count($ssItems) ?> <?= $e($t('bank_count')) ?></p>
<div class="chisimba-form-actions"><button type="submit" <?= !$ssItems?'disabled':'' ?>><?= $e($t('publish')) ?></button></div>
</form>
