<?php require __DIR__.'/common.php'; $o=$eventOccurrence; ?>
<h1><?=$esc($eventRecord['title'])?> — <?=$t('operations')?></h1>
<div class="chisimba-form-actions chisimba-form-actions--equal"><a class="button" href="<?=$url('scan',['id'=>$o['id']])?>"><?=$t('check_in')?></a><a class="button" href="<?=$url('attendance',['id'=>$o['id']])?>"><?=$t('attendance')?></a><?=$this->getObject('contextualhelp','help')->show('events','arrival',true)?></div>
<form method="post" action="<?=$url('maintain',['id'=>$o['id']])?>"><?php $csrf(); ?><button type="submit" class="button"><?=$t('maintain')?></button></form>
<p><?=$t('capacity')?>: <?=$esc($o['capacity'])?> · <?=$t('checked_in')?>: <?=count(array_filter($eventTickets,fn($r)=>(int)$r['checked_at']>0))?></p>
<table class="table"><caption><?=$t('bookings')?></caption><thead><tr><th><?=$t('reference')?></th><th><?=$t('booking_name')?></th><th><?=$t('email')?></th><th><?=$t('quantity')?></th><th><?=$t('status')?></th><th><?=$t('total')?></th></tr></thead><tbody>
<?php foreach($eventBookings as $b): ?><tr><td><?=$esc($b['id'])?></td><td><?=$esc($b['name'])?></td><td><?=$esc($b['email'])?></td><td><?=$esc($b['quantity'])?></td><td><?=$t($b['state'])?></td><td><?=$esc($b['currency'])?> <?=number_format($b['amount_minor']/100,2)?></td></tr><?php endforeach; ?></tbody></table>
<p><?=$t('refund_hint')?></p>
<h2><?=$t('reviews')?></h2>
<?php foreach($eventReviews as $r): ?>
<article class="chisimba-form-card"><p><?=$esc($r['rating'])?> / 5 · <?=$t($r['status'])?></p><p><?=nl2br($esc($r['comment']))?></p><p><?=$t($r['publish_consent']?'consent_yes':'consent_no')?></p>
<form method="post" action="<?=$url('moderate')?>"><?php $csrf(); $hidden('id',$r['id']); ?>
<label><?=$t('status')?> <select name="status"><?php if($r['publish_consent']): ?><option value="published"><?=$t('published')?></option><?php endif; ?><option value="hidden"><?=$t('hidden')?></option></select></label>
<label><?=$t('moderation_reason')?> <input name="reason" maxlength="500" value="<?=$esc($r['moderation_reason'])?>"></label><label><?=$t('organiser_reply')?> <textarea name="reply" rows="3"><?=$esc($r['reply'])?></textarea></label><button class="button" type="submit"><?=$t('save')?></button></form></article><?php endforeach; ?>
