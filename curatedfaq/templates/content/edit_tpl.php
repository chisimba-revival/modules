<?php
require __DIR__.'/common.php';$in=$faqInput;$new=($in['revision']??'new')==='new';
$editor=$this->newObject('htmlarea','htmlelements');$editor->name='answer';$editor->cssId='faq-answer';$editor->width='100%';$editor->setContent($in['answer']??'');
$this->appendArrayVar('headerParams','<script defer src="'.$e($this->getResourceUri('draftform.js','htmlelements')).'"></script>');
$key='curatedfaq:'.$this->getObject('user','security')->userId().':'.($new?'new':$in['id']);
?>
<main class="chisimba-form-page"><section class="chisimba-form-card chisimba-form-card--wide">
<h1><?= $e($t($new?'new':'edit')) ?></h1><div class="chisimba-form-actions"><?= $button('manage','arrow-left',['action'=>'manage']) ?><?= $this->getObject('contextualhelp','help')->show('curatedfaq','editing',true) ?></div>
<?php if($faqError!==''): ?><p role="alert"><?= $e($faqError) ?></p><?php endif; ?>
<?php if($this->getParam('saved')==='1'): ?><p role="status"><?= $e($t('saved')) ?></p><?php endif; ?>
<form method="post" class="chisimba-form" action="<?= $e($url(['action'=>'save'])) ?>" data-draft-form="<?= $e($key) ?>" data-draft-fields="id,revision,question,answer,status,display_order,categories[],new_category" data-token-url="<?= $e($url(['action'=>'token'])) ?>" data-draft-saving="<?= $e($t('saving')) ?>" data-draft-failure="<?= $e($t('failed')) ?>" data-draft-stored="<?= $e($t('local_draft')) ?>" data-draft-unavailable="<?= $e($t('storage_unavailable')) ?>">
<input type="hidden" name="id" value="<?= $e($in['id']) ?>"><input type="hidden" name="revision" value="<?= $e($in['revision']) ?>"><input type="hidden" name="csrf_token" value="<?= $e($faqToken) ?>">
<div data-draft-recovery hidden><p><?= $e($t('recover_notice')) ?></p><div class="chisimba-form-actions"><button type="button" class="button" data-draft-restore><?= $icon('rotate-ccw') ?><?= $e($t('restore')) ?></button><button type="button" class="button" data-draft-discard><?= $icon('x') ?><?= $e($t('discard')) ?></button></div></div>
<div class="chisimba-form-field"><label for="faq-question"><?= $e($t('question')) ?></label><input type="text" id="faq-question" name="question" maxlength="250" required value="<?= $e($in['question']??'') ?>"></div>
<div class="chisimba-form-field"><label for="faq-answer"><?= $e($t('answer')) ?></label><?= $editor->show() ?></div>
<div class="chisimba-form-field"><label for="faq-status"><?= $e($t('status')) ?></label><select id="faq-status" name="status"><?php foreach(['draft','published','archived'] as $status): ?><option value="<?= $status ?>" <?= ($in['status']??'draft')===$status?'selected':'' ?>><?= $e($t($status)) ?></option><?php endforeach; ?></select><p><?= $e($t('status_hint')) ?></p></div>
<div class="chisimba-form-field"><label for="faq-order"><?= $e($t('order')) ?></label><input id="faq-order" name="display_order" type="number" min="0" max="99999" required value="<?= $e($in['display_order']??'0') ?>"><p><?= $e($t('order_hint')) ?></p></div>
<div class="chisimba-form-field"><label for="faq-categories"><?= $e($t('category')) ?></label><select id="faq-categories" name="categories[]" multiple size="5"><?php foreach($faqCategories as $term): ?><option value="<?= $e($term['id']) ?>" <?= in_array($term['id'],is_array($in['categories']??null)?$in['categories']:[],true)?'selected':'' ?>><?= $e($term['name']) ?></option><?php endforeach; ?></select><p><?= $e($t('categories_hint')) ?></p></div>
<div class="chisimba-form-field"><label for="faq-new-category"><?= $e($t('new_category')) ?></label><input type="text" id="faq-new-category" name="new_category" maxlength="150" value="<?= $e($in['new_category']??'') ?>"></div>
<p role="status" aria-live="polite" data-draft-status></p>
<p><a href="<?= $e(html_entity_decode($this->uri(['action'=>'showlogin','return_to'=>$url(['action'=>'edit']+($new?[]:['id'=>$in['id']]))],'security'),ENT_QUOTES,'UTF-8')) ?>" target="_blank" rel="noopener"><?= $e($t('signin_link')) ?></a></p>
<div class="chisimba-form-actions"><button class="button chisimba-button-primary" type="submit"><?= $icon('save') ?><?= $e($t('save')) ?></button><?= $button('cancel','arrow-left',['action'=>'manage']) ?></div>
</form></section></main>
