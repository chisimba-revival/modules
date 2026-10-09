<?php
require __DIR__.'/common.php';
$sale=$shopDraft?:$shopSale;
$localDate=static fn($stamp)=>$stamp?(new DateTimeImmutable('@'.$stamp))->setTimezone(new DateTimeZone('Africa/Johannesburg'))->format('Y-m-d\TH:i'):'';
// Archived products are retained for order history, never offered in a promotion.
$shopBooks=array_values(array_filter($shopBooks,fn($book)=>$book['status']!=='archived' && !ShopRules::membership($book)));
$availableIds=array_column($shopBooks,'id');
$selected=array_values(array_intersect($sale['book_ids']??[],$availableIds));
?>
<main class="chisimba-workspace chisimba-stack">
<header class="chisimba-cluster"><h1><?=$t('sales')?></h1><a class="button chisimba-button-secondary" href="<?=$url('manage')?>"><?=$actionIcon('manage')?> <?=$t('manage')?></a><?=$this->getObject('contextualhelp','help')->show('shop','sales',true)?></header>
<?php if ($shopSale['revision']): ?>
<section class="chisimba-form-card chisimba-form-card--wide chisimba-flow">
<h2><?=$esc($shopSale['title'])?></h2><p><strong><?=$t($shopService->saleStatus($shopSale))?></strong> · <?=$esc($shopSale['percent'])?>% · <?=$esc(count(array_intersect($shopSale['book_ids'],$availableIds)))?> <?=$t('sale_selected_books')?></p>
<p><?=$t('saved_sale_help')?></p><a class="button" href="<?=$url('editsale')?>"><?=$actionIcon('edit')?> <?=$t('edit_sale')?></a>
</section>
<?php else: ?><p><?=$t('sale_intro')?></p><?php endif; ?>
<?php if(!empty($shopSale['enabled'])): ?><form method="post" action="<?=$url('stopsale')?>"><?php $csrf();$hidden('revision',$shopSale['revision']); ?><button class="button chisimba-button-secondary" type="submit"><?=$actionIcon('stop_sale')?> <?=$t('stop_sale')?></button></form><?php endif; ?>
<?php if (!empty($shopSaleEditing) || !$shopSale['revision']): ?>
<h2><?=$t($shopSale['revision']?'edit_sale':'sale_details')?></h2>
<form method="post" action="<?=$url('savesale')?>" class="chisimba-form chisimba-stack"><?php $csrf();$hidden('revision',$sale['revision']??$shopSale['revision']); ?>
<section class="chisimba-form-card chisimba-form-card--wide chisimba-flow"><h2><?=$t('sale_details')?></h2>
<?php $field('title',$sale['title']??'');$field('description',$sale['description']??'','textarea',false); ?>
<div class="chisimba-form-grid"><?php $field('percent',$sale['percent']??40,'number'); ?>
<div class="chisimba-form-field"><label for="sale-start"><?=$t('starts_at')?></label><input id="sale-start" type="datetime-local" name="starts_at" required value="<?=$esc($shopDraft?($sale['starts_at']??''):$localDate($sale['starts_at']??0))?>"></div>
<div class="chisimba-form-field"><label for="sale-end"><?=$t('ends_at')?></label><input id="sale-end" type="datetime-local" name="ends_at" required value="<?=$esc($shopDraft?($sale['ends_at']??''):$localDate($sale['ends_at']??0))?>"></div></div><p><?=$t('sale_timezone')?></p>
</section>
<section class="chisimba-form-card chisimba-form-card--wide chisimba-flow"><h2><?=$t('sale_books')?></h2><p><?=$t('sale_books_help')?></p>
<div class="chisimba-form-actions"><button type="button" class="button chisimba-button-secondary" data-sale-select="all" hidden><?=$actionIcon('save')?> <?=$t('select_all')?></button><button type="button" class="button chisimba-button-secondary" data-sale-select="none" hidden><?=$actionIcon('cancel')?> <?=$t('select_none')?></button></div>
<?php foreach($shopBooks as $book): ?><div class="chisimba-form-field"><label><input type="checkbox" name="book_ids[]" value="<?=$esc($book['id'])?>"<?=in_array($book['id'],$selected,true)?' checked':''?>> <?=$esc($book['title'])?> · <?=$t($book['status'])?> · <?=$money($book['price_minor'])?></label><span data-sale-price data-regular-price="<?=$esc($book['price_minor'])?>"><?=$t('sale_price')?>: <output><?= (int)$book['price_minor']>0 ? $money(max(1,(int)round((int)$book['price_minor']*(100-(int)($sale['percent']??40))/100))) : '—' ?></output></span></div><?php endforeach; ?>
</section>
<section class="chisimba-form-card chisimba-form-card--wide chisimba-flow"><h2><?=$t('sale_promotion')?></h2><p><?=$t('sale_promotion_help')?></p>
<?php $field('image_url',$sale['image_url']??'','url',false);$field('button_label',$sale['button_label']?:$shopService->text('shop_sale')); ?>
<img data-shop-cover class="chisimba-featured-image-preview"<?=empty($sale['image_url'])?' hidden':' src="'.$esc($sale['image_url']).'"'?> alt="<?=$esc($sale['title']??'')?>">
<div class="chisimba-form-actions"><button type="button" class="button chisimba-button-secondary" hidden data-shop-picker="<?=$esc($this->uri(['action'=>'filepicker','target'=>'shop-image_url','policy'=>'image','location'=>'user'],'filemanager'))?>"><?=$actionIcon('choose_image')?> <?=$t('choose_image')?></button></div>
</section>
<section class="chisimba-form-card chisimba-form-card--wide chisimba-flow"><label><input name="enabled" type="checkbox" value="1"<?=!empty($sale['enabled'])?' checked':''?>> <?=$t('enable_sale')?></label><p><?=$t('sale_enable_help')?></p><div class="chisimba-form-actions"><button class="button" type="submit"><?=$actionIcon('save')?> <?=$t('save_sale')?></button><a class="button chisimba-button-secondary" href="<?=$url('manage')?>"><?=$actionIcon('cancel')?> <?=$t('cancel')?></a></div></section>
</form><?php endif; ?></main>
