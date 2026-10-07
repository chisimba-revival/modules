<?php require __DIR__.'/common.php'; $order=$shopOrder; $q=json_decode($order['snapshot'],true); $address=json_decode($order['address_json'],true); $managed=!empty($shopManaged); ?>
<main class="chisimba-workspace chisimba-stack"><header class="chisimba-cluster"><h1><?=$t('order')?></h1><?php if (!$managed): ?><a class="button chisimba-button-secondary" href="<?=$url('cart')?>"><?=$actionIcon('cart')?> <?=$t('cart')?></a><?php endif; ?><?=$this->getObject('contextualhelp','help')->show('shop',$managed?'fulfilment':'buying',true)?><?php if ($managed): ?><a class="button chisimba-button-secondary" href="<?=$url('orders')?>"><?=$actionIcon('orders')?> <?=$t('orders')?></a><?php endif; ?></header>
<p><?=$t('reference')?>: <?=$esc($order['id'])?></p><p role="status"><?=$t($order['payment_state'])?> · <?=$t($order['fulfilment_state'])?></p>
<?php if ($order['fulfilment_state']==='review'): ?><p class="chisimba-form-notice"><?=$t('review_help')?></p><?php endif; ?>
<div class="chisimba-form-card">
<?php foreach ($q['lines'] as $line): ?><p><?=$esc($line['quantity'])?> × <?=$esc($line['title'])?> — <?=$money($line['total_minor'])?></p><?php if (!empty($line['components'])): ?><ul><?php foreach($line['components'] as $child): ?><li><?=$esc($line['quantity']*$child['quantity'])?> × <?=$esc($child['title'])?></li><?php endforeach; ?></ul><?php endif; ?><?php endforeach; ?>
<dl class="chisimba-details"><dt><?=$t('subtotal')?></dt><dd><?=$money($q['subtotal_minor'])?></dd><dt><?=$t('shipping')?></dt><dd><?=$money($q['shipping_minor'])?></dd><dt><?=$t('total')?></dt><dd><strong><?=$money($q['amount_minor'])?></strong></dd></dl>
<h2><?=$t('delivery_details')?></h2><address><?php foreach ($address as $key=>$value): ?><?=$esc($key==='country'?$shopService->text('south_africa'):$value)?><br><?php endforeach; ?></address>
<?php if ($order['courier']): ?><p><?=$t('courier')?>: <?=$esc($order['courier'])?> · <?=$t('tracking')?>: <?=$esc($order['tracking'])?></p><?php endif; ?>
</div>
<?php if (!$managed): ?>
<?php if ($order['payment_state']==='paid'): ?><p class="chisimba-form-notice" role="status"><?=$t('payment_confirmed_help')?></p><?php endif; ?>
<?php if ($order['payment_state']==='unpaid' && $order['fulfilment_state']==='held' && $order['hold_until']>time()): ?>
<p><?=$t('hold_help')?></p><form method="post" action="<?=$url('checkout')?>" class="chisimba-cluster"><?php $csrf(); $hidden('token',$shopToken); ?><button type="submit" class="button"><?=$actionIcon('pay')?> <?=$t('pay')?></button></form>
<?php elseif ($order['payment_state']==='unpaid'): ?><p><?=$t('order_expired')?></p><?php endif; ?>
<?php if ($order['intent_id'] && $order['payment_state']==='unpaid'): ?><form method="post" action="<?=$url('reconcile')?>" class="chisimba-form-actions chisimba-form-actions--equal"><?php $csrf(); $hidden('token',$shopToken); ?><button class="button chisimba-button-secondary" type="submit"><?=$actionIcon('check_payment')?> <?=$t('check_payment')?></button></form><?php endif; ?>
<?php else: ?>
<?php if ($order['payment_state']==='paid' && $order['fulfilment_state']==='packing'): ?>
<form method="post" action="<?=$url('dispatchorder')?>" class="chisimba-form-card chisimba-form chisimba-flow"><?php $csrf(); $hidden('id',$order['id']); $hidden('revision',$order['revision']); ?><h2><?=$t('dispatch')?></h2><div class="chisimba-form-grid"><?php $field('courier',$shopDraft['courier']??''); $field('tracking',$shopDraft['tracking']??''); ?></div><button class="button" type="submit"><?=$actionIcon('mark_dispatched')?> <?=$t('mark_dispatched')?></button></form>
<?php endif; ?>
<div class="chisimba-cluster"><?php foreach (['reconcileadmin'=>'check_payment','retrynotice'=>'retry_notice'] as $action=>$label): ?><form method="post" action="<?=$url($action)?>"><?php $csrf(); $hidden('id',$order['id']); ?><button class="button chisimba-button-secondary" type="submit"><?=$actionIcon($label)?> <?=$t($label)?></button></form><?php endforeach; ?></div>
<?php
$actions=[];
if ($order['payment_state']==='unpaid' && $order['fulfilment_state']==='held') $actions['cancel']='cancel_order';
if ($order['fulfilment_state']==='dispatched') $actions['delivered']='mark_delivered';
if ($order['payment_state']==='paid' && $order['fulfilment_state']==='review' && !(int)$order['stock_applied']) $actions['allocate']='allocate_stock';
if (in_array($order['payment_state'],['refunded','reversed'],true) && $order['fulfilment_state']==='review' && (int)$order['stock_applied']===1) $actions['restock']='restock_unsent';
?>
<div class="chisimba-form-actions chisimba-form-actions--equal"><?php foreach ($actions as $operation=>$label): ?><form method="post" action="<?=$url('fulfilment')?>"><?php $csrf(); $hidden('id',$order['id']); $hidden('revision',$order['revision']); $hidden('operation',$operation); ?><button class="button chisimba-button-secondary" type="submit"><?=$actionIcon($label)?> <?=$t($label)?></button></form><?php endforeach; ?></div>
<h2><?=$t('history')?></h2><ul><?php foreach ($shopService->orderHistory($order['id']) as $entry): ?><li><?=$esc(gmdate('Y-m-d H:i',$entry['created_at']))?> UTC — <?=$t($entry['event'])?></li><?php endforeach; ?></ul>
<?php endif; ?></main>
