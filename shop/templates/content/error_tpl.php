<?php require __DIR__.'/common.php'; ?>
<main class="chisimba-form-card"><h1><?=$t('attention')?></h1><p><?=$t('error_help')?></p>
<?php if (!empty($shopDraft)): ?><details><summary><?=$t('saved_input')?></summary><dl><?php foreach ($shopDraft as $key=>$value): if (!is_string($value)||!in_array($key,['title','description','isbn','price','stock','image_url','status','name','email','phone','address_line','suburb','city','province','postal_code','country','courier','tracking'],true)) continue; ?><dt><?=$t($key==='name'?'customer_name':$key)?></dt><dd><?=$esc($value)?></dd><?php endforeach; ?></dl></details><?php endif; ?>
<a class="button chisimba-button-secondary" href="<?=$url('cart')?>"><?=$actionIcon('cart')?> <?=$t('cart')?></a></main>
