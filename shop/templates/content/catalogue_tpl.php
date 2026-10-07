<?php require __DIR__.'/common.php'; ?>
<main class="chisimba-workspace chisimba-stack"><header class="chisimba-cluster"><h1><?=$t('shop')?></h1>
<a class="button" href="<?=$url('cart')?>"><?=$actionIcon('cart')?> <?=$t('cart')?></a>
<?php if ($shopService->canManage()): ?><a class="button chisimba-button-secondary" href="<?=$url('manage')?>"><?=$actionIcon('manage')?> <?=$t('manage')?></a><?php endif; ?>
<?=$this->getObject('contextualhelp','help')->show('shop','buying',true)?></header>
<p><?=$t('delivery_only')?></p>
<?php if (!$shopBooks): ?><p><?=$t('no_books')?></p><?php endif; ?>
<div class="chisimba-form-grid">
<?php foreach ($shopBooks as $book): ?>
<article class="chisimba-form-card">
<?php if ($book['image_url']): ?><img class="chisimba-featured-image-preview" src="<?=$esc($book['image_url'])?>" alt="<?=$esc($book['title'])?>" loading="lazy"><?php endif; ?>
<h2><a href="<?=$url('view',['id'=>$book['id']])?>"><?=$esc($book['title'])?></a></h2>
<p><?=nl2br($esc($book['description']))?></p>
<?php if (!empty($book['components'])): ?><p><?=$t('combo_books')?>:</p><ul><?php foreach($book['components'] as $child): ?><li><?=$esc($child['title'])?></li><?php endforeach; ?></ul><?php endif; ?>
<?php if ($book['isbn']): ?><p><?=$t('isbn')?>: <?=$esc($book['isbn'])?></p><?php endif; ?>
<p><?php $bookPrice($book); ?></p>
<?php if ((int)$book['stock'] > 0): ?>
<form method="post" action="<?=$url('add')?>" class="chisimba-cluster"><?php $csrf(); $hidden('id',$book['id']); ?>
<label for="quantity-<?=$esc($book['id'])?>"><?=$t('quantity')?></label><input id="quantity-<?=$esc($book['id'])?>" name="quantity" type="number" value="1" min="1" max="100" required>
<button class="button" type="submit"><?=$actionIcon('add_to_cart')?> <?=$t('add_to_cart')?></button></form>
<?php else: ?><p><?=$t('out_of_stock')?></p><?php endif; ?>
</article><?php endforeach; ?></div></main>
