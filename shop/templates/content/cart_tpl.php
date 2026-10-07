<?php require __DIR__.'/common.php'; $draft=$shopDraft??[]; ?>
<main class="chisimba-workspace chisimba-stack"><header class="chisimba-cluster"><h1><?=$t('cart')?></h1><a class="button chisimba-button-secondary" href="<?=$url('catalogue')?>"><?=$actionIcon('continue_shopping')?> <?=$t('continue_shopping')?></a><?=$this->getObject('contextualhelp','help')->show('shop','buying',true)?></header>
<?php if (!$shopCart): ?><p><?=$t('empty_cart')?></p><?php else: ?>
<div class="chisimba-publishing-layout"><section class="chisimba-stack">
<form method="post" action="<?=$url('updatecart')?>" class="chisimba-form-card chisimba-form-card--wide chisimba-form chisimba-flow"><?php $csrf(); ?>
<?php foreach ($shopCart as $id=>$quantity): $book=$shopService->book($id); ?>
<div class="chisimba-form-grid"><div><strong><?=$esc($book['title']??$shopService->text('book_unavailable'))?></strong><?php if ($book): ?><p><?php $bookPrice($book); ?></p><?php endif; ?></div><div class="chisimba-form-field"><label for="cart-<?=$esc($id)?>"><?=$t('quantity')?> — <?=$esc($book['title']??$shopService->text('book_unavailable'))?></label>
<input id="cart-<?=$esc($id)?>" type="number" name="quantity[<?=$esc($id)?>]" value="<?=$esc($quantity)?>" min="0" max="100" required></div><div class="chisimba-form-actions"><button class="button chisimba-button-secondary" type="submit" name="remove_book" value="<?=$esc($id)?>" aria-label="<?=$t('remove_item')?>: <?=$esc($book['title']??'')?>"><?=$actionIcon('remove_item')?> <?=$t('remove_item')?></button></div></div>
<?php endforeach; ?>
<div class="chisimba-form-actions"><button class="button" type="submit"><?=$actionIcon('update_cart')?> <?=$t('update_cart')?></button><button class="button chisimba-button-secondary" type="submit" name="clear_cart" value="1" formnovalidate><?=$actionIcon('clear_cart')?> <?=$t('clear_cart')?></button></div></form>
<?php if ($shopQuote): ?>
<?php if ($shopService->ready()): ?>
<form method="post" action="<?=$url('prepare')?>" class="chisimba-form-card chisimba-form-card--wide chisimba-form chisimba-flow"><?php $csrf(); $hidden('request_key',$shopRequest); $hidden('quote_hash',$shopService->quoteHash($shopQuote)); ?>
<h2><?=$t('delivery_details')?></h2><p><?=$t('delivery_only')?></p>
<div class="chisimba-form-grid"><?php foreach (['name','email','phone','address_line','suburb','city','province','postal_code'] as $name) $field($name,$draft[$name]??'',$name==='email'?'email':($name==='phone'?'tel':'text'),$name!=='suburb'); ?></div>
<div class="chisimba-form-field"><label for="shop-country"><?=$t('country')?></label><select id="shop-country" name="country"><option value="ZA"><?=$t('south_africa')?></option></select></div>
<details class="chisimba-form-disclosure"><summary><?=$t('terms')?></summary><p><?=nl2br($esc($shopSettings['terms']))?></p></details>
<p><label><input type="checkbox" name="accept_terms" value="1" required<?=($draft['accept_terms']??'')==='1'?' checked':''?>> <?=$t('accept_terms')?></label></p>
<?php foreach (['issued_at','nonce','signature'] as $key) $hidden('abuse_'.$key,$shopAbuse[$key]??''); ?>
<div hidden aria-hidden="true"><label for="shop-website">Website</label><input id="shop-website" name="website" tabindex="-1" autocomplete="off"></div>
<div class="chisimba-form-actions chisimba-form-actions--equal"><button type="submit" class="button"><?=$actionIcon('review_order')?> <?=$t('review_order')?></button><span><?=$t('review_before_payment')?></span></div></form>
<?php else: ?><p role="status"><?=$t('checkout_unavailable')?></p><?php endif; ?>
<?php endif; ?></section><?php if ($shopQuote): ?><aside class="chisimba-form-card chisimba-sticky-summary"><dl class="chisimba-details"><dt><?=$t('subtotal')?></dt><dd><?=$money($shopQuote['subtotal_minor'])?></dd><dt><?=$t('shipping')?></dt><dd><?=$money($shopQuote['shipping_minor'])?></dd><dt><?=$t('total')?></dt><dd><strong><?=$money($shopQuote['amount_minor'])?></strong></dd></dl><p><?=$t('shipping_hint')?></p>
<details><summary><?=$t('shipping_costs')?></summary><dl class="chisimba-details"><?php foreach ($shopSettings['zones']['ZA']['bands'] as $band): ?><dt><?=$esc($band['from'])?> <?=$t('books_from')?></dt><dd><?=$money($band['amount_minor'])?></dd><?php endforeach; ?></dl></details></aside>
<?php endif; ?></div><?php endif; ?></main>
