<?php require __DIR__.'/common.php'; ?>
<h1><?=$t('check_in')?> — <?=$esc($eventRecord['title'])?></h1>
<?=$this->getObject('contextualhelp','help')->show('events','arrival',true)?>
<p id="scan-status" role="status" data-camera-unavailable="<?=$t('camera_unavailable')?>" data-scanned="<?=$t('scanned')?>"><?=$t('scan_hint')?></p>
<video id="scan-video" autoplay playsinline muted width="320" hidden></video>
<div class="chisimba-form-actions chisimba-form-actions--equal"><button type="button" class="button" id="start-camera"><?=$t('start_camera')?></button><button type="button" class="button" id="stop-camera" hidden><?=$t('stop_camera')?></button></div>
<form class="chisimba-form-card" method="post" action="<?=$url('checkin',['id'=>$eventOccurrence['id']])?>"><?php $csrf(); $field('code',$eventDraft['code']??'','text',true); ?><button type="submit" class="button"><?=$t('check_in')?></button></form>
<a class="button chisimba-button-secondary" href="<?=$url('attendance',['id'=>$eventOccurrence['id']])?>"><?=$t('attendance')?></a>

<details><summary><?=$t('find_attendee')?></summary>
<label><?=$t('find_attendee')?> <input type="search" data-attendee-search autocomplete="off"></label>
<ul data-attendee-list>
<?php foreach($eventTickets as $ticket): ?>
<li data-attendee-name="<?=$esc(mb_strtolower($ticket['attendee']))?>"><span><?=$esc($ticket['attendee'])?></span>
<?php if($ticket['state']!=='valid'): ?> — <?=$t('cancelled')?>
<?php elseif((int)$ticket['checked_at']>0): ?> — <?=$t('already_checked')?>
<?php else: ?><form method="post" action="<?=$url('checkin',['id'=>$eventOccurrence['id']])?>"><?php $csrf(); $hidden('code',$eventPolicy->ticketCode($ticket)); ?><button type="submit" class="button"><?=$t('check_in')?></button></form><?php endif; ?></li>
<?php endforeach; ?></ul></details>
