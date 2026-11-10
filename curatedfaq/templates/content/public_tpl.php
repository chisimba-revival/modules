<?php require __DIR__.'/common.php'; if(empty($faqPreview)){ $destination=['module'=>'curatedfaq','action'=>'view'];if((int)$this->getParam('page',1)>1)$destination['page']=(int)$this->getParam('page');foreach($this->getObject('pagemetadata','ui')->build($t('title'),$t('title'),$destination) as $key=>$value)$this->setVar($key,$value); } ?>
<main class="chisimba-form-page"><section class="chisimba-form-card chisimba-form-card--wide">
<header><h1><?= $e($t('title')) ?></h1><div class="chisimba-form-actions"><?php if($service->canManage())echo $button('manage','settings',['action'=>'manage']); ?></div></header>
<?php if(!empty($faqPreview)): ?><p role="status"><?= $e($t('preview_notice')) ?></p><?php else: require __DIR__.'/search.php'; endif; ?>
<?php if(!$faqRows): ?><p role="status"><?= $e($t('empty')) ?></p><?php endif; ?>
<div class="chisimba-flow">
<?php foreach($faqRows as $row): ?><details class="chisimba-card chisimba-disclosure" id="faq-<?= $e($row['id']) ?>" <?= !empty($faqPreview)?'open':'' ?>>
<summary><?= $e($row['question']) ?></summary>
<div class="chisimba-richtext"><?= $this->getObject('richtextsanitizer','utilities')->cleanHtml($row['answer']) ?></div>
<?php $terms=$service->itemCategories($row['id']);if($terms): ?><p><?= $e($t('category')) ?>: <?= $e(implode(', ',array_column($terms,'name'))) ?></p><?php endif; ?>
</details><?php endforeach; ?>
</div><?php require __DIR__.'/pagination.php'; ?></section></main>
