<form method="get" class="chisimba-filter-form" action="<?= $e($url()) ?>">
<input type="hidden" name="module" value="curatedfaq"><input type="hidden" name="action" value="<?= !empty($managing)?'manage':'view' ?>">
<div class="chisimba-form-field"><label for="faq-search"><?= $e($t('search')) ?></label><input id="faq-search" name="q" type="search" maxlength="200" value="<?= $e(is_string($this->getParam('q'))?$this->getParam('q'):'') ?>"></div>
<div class="chisimba-form-field"><label for="faq-category"><?= $e($t('category')) ?></label><select id="faq-category" name="category"><option value=""><?= $e($t('all_categories')) ?></option><?php foreach($faqCategories as $term): ?><option value="<?= $e($term['id']) ?>" <?= $this->getParam('category')===$term['id']?'selected':'' ?>><?= $e($term['name']) ?></option><?php endforeach; ?></select></div>
<button class="button" type="submit" aria-label="<?= $e($t('search')) ?>"><?= $icon('search') ?></button>
<?= $button('reset','rotate-ccw',['action'=>!empty($managing)?'manage':'view']) ?>
<?php if(empty($managing))echo $this->getObject('contextualhelp','help')->show('curatedfaq','reading',true); ?>
</form>
