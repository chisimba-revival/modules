<?php require __DIR__.'/common.php'; $data=$eventTicketData; $ticket=$data['ticket']; $o=$data['occurrence']; $e=$data['event']; $private=$eventService->details($o,'private_details'); ?>
<h1><?=$t('your_ticket')?></h1>
<article id="event-ticket" class="chisimba-form-card chisimba-form-card--wide">
<h2><?=$esc($e['title'])?></h2><p><?=$esc($ticket['attendee'])?></p><p><?=$esc($eventService->date($o['starts_at'],$o['timezone']))?> · <?=$esc($o['timezone'])?></p>
<div data-ticket-qr="<?=$esc($eventPolicy->ticketCode($ticket))?>" aria-label="<?=$t('ticket_qr')?>"></div>
<p><?=$t('ticket_code')?>: <strong><?=$esc(chunk_split($eventPolicy->ticketCode($ticket),5,' '))?></strong></p>
<?php foreach(['meeting','directions','parking'] as $key): ?><h3><?=$t($key)?></h3><p><?=nl2br($esc($private[$key]))?></p><?php endforeach; ?>
<?php if(!empty($eventService->details($o)['change_message'])): ?><h3><?=$t('event_update')?></h3><p><?=nl2br($esc($eventService->details($o)['change_message']))?></p><?php endif; ?>
<p><?=$t('keep_private')?></p></article>
<div class="chisimba-form-actions chisimba-form-actions--equal"><button type="button" class="button" data-download-ticket disabled><?=$t('download_ticket')?></button><button type="button" class="button" data-print-ticket><?=$t('print_ticket')?></button><a class="button" href="<?=$url('calendar',['token'=>$eventToken])?>"><?=$t('calendar')?></a><?=$this->getObject('contextualhelp','help')->show('events','tickets',true)?></div>
<p><?=$t('download_hint')?></p>
<?php if(time()>=(int)$o['ends_at']): ?><h2><?=$t('followup')?></h2><p><?=nl2br($esc($eventService->details($e)['followup']))?></p><?php endif; ?>
<?php if((int)$ticket['checked_at']>0&&time()>=(int)$o['ends_at']): ?>
<form class="chisimba-form-card" method="post" action="<?=$url('review')?>"><h2><?=$t('leave_review')?></h2><?php $csrf(); $hidden('token',$eventToken); ?>
<label><?=$t('rating')?> <select name="rating" required><?php foreach([5,4,3,2,1] as $n): ?><option value="<?=$n?>"><?=$n?> / 5</option><?php endforeach; ?></select></label>
<?php $field('comment','','textarea',true); $field('display_name'); ?><label><input type="checkbox" name="publish_consent" value="1"> <?=$t('publish_consent')?></label><div class="chisimba-form-actions"><button type="submit" class="button"><?=$t('save_review')?></button></div></form><?php endif; ?>
