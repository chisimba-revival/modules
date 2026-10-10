<?php require __DIR__.'/common.php'; $b=$eventBooking; ?>
<h1><?=$t('your_booking')?></h1>
<h2><?=$esc($eventRecord['title'])?></h2><p><?=$esc($eventService->date($eventOccurrence['starts_at'],$eventOccurrence['timezone']))?> · <?=$esc($eventOccurrence['timezone'])?></p>
<?php if(!empty($eventService->details($eventRecord)['contact'])): ?><p><?=$t('contact')?>: <?=$esc($eventService->details($eventRecord)['contact'])?></p><?php endif; ?>
<p><?=$t('reference')?>: <?=$esc($b['id'])?></p><p role="status"><?=$t($b['state'])?></p>
<p><?=$esc($b['quantity'])?> <?=$t('tickets')?> · <?=$esc($b['currency'])?> <?=number_format($b['amount_minor']/100,2)?></p>
<p><?=$t('vat_included')?>: <?=$esc($b['currency'])?> <?=number_format($b['vat_minor']/100,2)?></p>
<?=$this->getObject('contextualhelp','help')->show('events','tickets',true)?>
<?php if($b['state']==='held'&&(int)$b['expires_at']>time()): ?>
<p><?=$t('hold_hint')?></p><form method="post" action="<?=$url('checkout')?>"><?php $csrf(); $hidden('token',$eventToken); ?><button class="button" type="submit"><?=$t('pay')?></button></form>
<?php elseif($b['state']==='held'): ?><p><?=$t('hold_expired')?></p><?php endif; ?>
<?php if($b['state']==='confirmed'): foreach($eventBookingTickets as $ticket): if($ticket['state']!=='valid')continue; ?>
<section class="chisimba-form-card"><h2><?=$esc($ticket['attendee'])?></h2><a class="button" href="<?=$url('ticket',['token'=>$eventPolicy->token('ticket',$ticket['id'],$ticket['revision'])])?>"><?=$t('open_ticket')?></a>
<details><summary><?=$t('transfer')?></summary><p><?=$t('transfer_hint')?></p><form method="post" action="<?=$url('transfer')?>">
<?php $csrf(); $hidden('token',$eventToken); $hidden('ticket_id',$ticket['id']); ?>
<label><?=$t('attendee')?> <input name="attendee" required maxlength="191" value="<?=$esc($ticket['attendee'])?>"></label><button type="submit" class="button"><?=$t('transfer')?></button></form></details></section>
<?php endforeach; endif; ?>
<a href="<?=$url('booking',['token'=>$eventToken])?>"><?=$t('refresh')?></a>
