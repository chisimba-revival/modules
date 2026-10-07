<?php require __DIR__.'/common.php'; ?>
<main class="chisimba-workspace chisimba-stack"><header class="chisimba-cluster"><h1><?=$t('orders')?></h1><a class="button chisimba-button-secondary" href="<?=$url('manage')?>"><?=$actionIcon('manage')?> <?=$t('manage')?></a><?=$this->getObject('contextualhelp','help')->show('shop','fulfilment',true)?></header><p><?=$t('recent_orders')?></p>
<?php if (!$shopOrders): ?><p><?=$t('no_orders')?></p><?php endif; ?>
<?php foreach ($shopOrders as $order): $q=json_decode($order['snapshot'],true); ?>
<article class="chisimba-form-card"><h2><a href="<?=$url('operation',['id'=>$order['id']])?>"><?=$esc($order['id'])?></a></h2><p><?=$esc($order['email'])?> · <?=$money($q['amount_minor'])?> · <?=$t($order['payment_state'])?> · <?=$t($order['fulfilment_state'])?></p><a class="button" href="<?=$url('operation',['id'=>$order['id']])?>"><?=$actionIcon('shipping_settings')?> <?=$t($order['payment_state']==='paid' && $order['fulfilment_state']==='packing'?'dispatch':'view_order')?></a></article>
<?php endforeach; ?></main>
