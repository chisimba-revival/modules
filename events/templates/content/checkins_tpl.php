<?php require __DIR__.'/common.php'; ?>
<header class="chisimba-page-heading"><h1><?=$t('my_checkins')?></h1><?=$this->getObject('contextualhelp','help')->show('events','arrival',true)?></header>
<p><?=$t('checkins_intro')?></p><p><?=$t('checkin_window_hint')?></p>
<?php if(!$eventAssignments): ?><p><?=$t('no_checkins')?></p><?php endif; ?>
<div class="chisimba-publication-card-grid"><?php foreach($eventAssignments as $assignment): ?>
<article class="chisimba-publication-card"><div class="chisimba-publication-card__body"><h2><?=$esc($assignment['title'])?></h2><p><?=$esc($eventService->date($assignment['starts_at'],$assignment['timezone']))?></p><a class="button" href="<?=$url('scan',['id'=>$assignment['id']])?>"><?=$t('check_tickets')?></a></div></article>
<?php endforeach; ?></div>
