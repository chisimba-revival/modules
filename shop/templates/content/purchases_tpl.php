<?php require __DIR__.'/common.php'; ?>
<main class="chisimba-workspace chisimba-stack"><header class="chisimba-cluster"><h1><?=$t('my_purchases')?></h1><a class="button" href="<?=$url('catalogue')?>"><?=$t('shop')?></a><?=$this->getObject('contextualhelp','help')->show('shop','buying',true)?></header>
<p><?=$t('purchases_help')?></p>
<?php foreach($shopPurchases??[] as $purchase): $snapshot=json_decode($purchase['snapshot'],true,512,JSON_THROW_ON_ERROR); ?>
<section class="chisimba-form-card"><h2><a href="<?=$url('order',['token'=>$shopService->token($purchase['id'])])?>"><?=$t('order')?> <?=$esc($purchase['id'])?></a></h2><p><?=$t($purchase['payment_state'])?></p>
<?php foreach($snapshot['lines'] as $line): ?><h3><?=$esc($line['title'])?></h3>
<?php if($purchase['payment_state']==='paid' && ($line['virtual']['kind']??'')==='download'): ?><ul>
<?php foreach($line['virtual']['file_ids'] as $index=>$fileId): ?><li><a href="<?=$url('download',['order_id'=>$purchase['id'],'file_id'=>$fileId])?>"><?=$t('download')?> <?=$index+1?></a></li><?php endforeach; ?></ul><?php endif; ?>
<?php endforeach; ?></section><?php endforeach; ?></main>
