<?php
/** Native ShortURL editor; all displayed values are escaped. */
if (empty($GLOBALS['kewl_entry_point_run'])) die('You cannot view this page directly');
$e=fn($value)=>htmlspecialchars((string)$value,ENT_QUOTES,'UTF-8');
$t=fn($key)=>$this->text($key);
$route=fn($params)=>html_entity_decode($this->uri($params,'shorturl'),ENT_QUOTES,'UTF-8');
$input=$this->getVar('shorturlInput',[]);
$rows=$this->getVar('shorturlRows',[]);
$error=$this->getVar('shorturlError','');
?>
<h1><?= $e($t('title')) ?></h1>
<?= $this->getObject('contextualhelp','help')->show('shorturl','editing') ?>
<p><?= $e($t('summary')) ?></p>
<div role="status" aria-live="polite" data-shorturl-status><?= $e($error) ?></div>
<form method="post" action="<?= $e($route(['action'=>'save'])) ?>" class="chisimba-form-card chisimba-form-card--wide" data-shorturl-form data-shorturl-save data-failure="<?= $e($t('request_savefailed')) ?>" data-uncertain="<?= $e($t('uncertain')) ?>" data-return="<?= $e($route(['action'=>'list'])) ?>">
<h2><?= $e($t(empty($input['id'])?'add':'edit')) ?></h2>
<?= $this->policy->tokenField() ?>
<input type="hidden" name="id" value="<?= $e($input['id']??'') ?>">
<input type="hidden" name="revision" value="<?= $e($input['revision']??'') ?>">
<?php foreach (['txtMatchUrl'=>'match','txtTargetUrl'=>'target','ordering'=>'order'] as $name=>$label): ?>
<div class="chisimba-form-field"><label for="shorturl-<?= $e($name) ?>"><?= $e($t($label)) ?></label>
<input id="shorturl-<?= $e($name) ?>" name="<?= $e($name) ?>" type="<?= $name==='ordering'?'number':'text' ?>" <?= $name==='ordering'?'min="0" max="999999999"':'maxlength="255"' ?> required value="<?= $e($input[$name]??($name==='ordering'?'0':'')) ?>"></div>
<?php endforeach ?>
<div class="chisimba-form-actions"><button type="submit"><?= $e($t('save')) ?></button><a class="button" href="<?= $e($route(['action'=>'list'])) ?>"><?= $e($t('new')) ?></a></div>
</form>
<h2><?= $e($t('mappings')) ?></h2>
<?php if (!$rows): ?><p><?= $e($t('empty')) ?></p><?php endif ?>
<?php foreach ($rows as $row): ?>
<section class="chisimba-form-card">
<h3><?= $e($row['match_url']) ?></h3><p><?= $e($row['target_url']) ?></p>
<?php if ((int)$row['is_dynamic']!==0): ?><p><?= $e($t('dynamic')) ?></p>
<?php else: ?>
<div class="chisimba-form-actions">
<a class="button" href="<?= $e($route(['action'=>'edit','id'=>$row['id']])) ?>"><?= $e($t('edit')) ?></a>
<form method="post" action="<?= $e($route(['action'=>'delete'])) ?>" data-shorturl-form data-confirm="<?= $e($t('confirmdelmapping')) ?>" data-failure="<?= $e($t('request_savefailed')) ?>" data-uncertain="<?= $e($t('uncertain')) ?>" data-return="<?= $e($route(['action'=>'list'])) ?>">
<?= $this->policy->tokenField() ?><input type="hidden" name="id" value="<?= $e($row['id']) ?>"><input type="hidden" name="revision" value="<?= $e($this->repository->revision($row)) ?>">
<label data-shorturl-confirm><input type="checkbox" name="confirm" value="yes" required> <?= $e($t('confirmdelmapping')) ?></label>
<button type="submit"><?= $e($t('delete')) ?></button>
</form></div>
<?php endif ?></section>
<?php endforeach ?>
