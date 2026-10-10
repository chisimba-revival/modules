<?php require __DIR__.'/common.php'; ?>
<h1><?=$t('manage')?></h1><div class="chisimba-form-actions chisimba-form-actions--equal"><a class="button" href="<?=$url('edit')?>"><?=$t('new_event')?></a><a class="button chisimba-button-secondary" href="<?=$url('catalogue')?>"><?=$t('all_events')?></a><?=$this->getObject('contextualhelp','help')->show('events','organising',true)?></div>
<?php foreach($eventRecords as $e): ?>
<article class="chisimba-form-card"><h2><?=$esc($e['title'])?></h2><p><?=$t($e['status'])?></p>
<div class="chisimba-form-actions chisimba-form-actions--equal"><a class="button" href="<?=$url('edit',['id'=>$e['id']])?>"><?=$t('edit_details')?></a><a class="button" href="<?=$url('schedule',['event_id'=>$e['id']])?>"><?=$t('add_date')?></a><a class="button chisimba-button-secondary" href="<?=$url('view',['id'=>$e['id']])?>"><?=$t('public_page')?></a></div>
<?php foreach($this->getObject('eventstore')->rows('occurrences',['event_id'=>$e['id']]) as $o): ?>
<p><?=$esc($eventService->date($o['starts_at'],$o['timezone']))?></p><div class="chisimba-form-actions chisimba-form-actions--equal"><a class="button" href="<?=$url('schedule',['id'=>$o['id']])?>"><?=$t('edit_date')?></a><a class="button" href="<?=$url('schedule',['copy'=>$o['id']])?>"><?=$t('repeat_date')?></a><a class="button" href="<?=$url('operations',['id'=>$o['id']])?>"><?=$t('operations')?></a></div>
<?php endforeach; ?></article><?php endforeach; ?>
