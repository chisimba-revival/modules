<?php require __DIR__.'/common.php'; $managing=true; ?>
<main class="chisimba-form-page"><section class="chisimba-form-card chisimba-form-card--wide">
<h1><?= $e($t('manage')) ?></h1><div class="chisimba-form-actions"><?= $button('new','plus',['action'=>'edit']).$button('public','eye',['action'=>'view']) ?><?= $this->getObject('contextualhelp','help')->show('curatedfaq','editing',true) ?></div>
<?php require __DIR__.'/search.php'; ?>
<?php if(!$faqRows): ?><p><?= $e($t('empty')) ?></p><?php endif; ?>
<div class="chisimba-flow"><?php foreach($faqRows as $row): ?><section class="chisimba-card"><h2><?= $e($row['question']) ?></h2><p><?= $e($t($row['status'])) ?> · <?= $e($t('order')) ?>: <?= (int)$row['display_order'] ?></p><div class="chisimba-form-actions">
<a class="chisimba-icon-button" href="<?= $e($url(['action'=>'edit','id'=>$row['id']])) ?>" aria-label="<?= $e($t('edit').' '.$row['question']) ?>"><?= $icon('pencil') ?></a>
<a class="chisimba-icon-button" href="<?= $e($url(['action'=>'preview','id'=>$row['id']])) ?>" aria-label="<?= $e($t('preview').' '.$row['question']) ?>"><?= $icon('eye') ?></a>
</div></section><?php endforeach; ?></div><?php require __DIR__.'/pagination.php'; ?></section></main>
