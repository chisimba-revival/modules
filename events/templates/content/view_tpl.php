<?php
require __DIR__.'/common.php';
$e=$eventPublic; $d=$e['details']; $upcoming=[]; $past=[];
foreach($e['occurrences'] as $date) { if((int)$date['ends_at']<=time()) $past[]=$date; else $upcoming[]=$date; }
usort($upcoming,static fn($a,$b)=>(int)$a['starts_at']<=>(int)$b['starts_at']);
usort($past,static fn($a,$b)=>(int)$b['starts_at']<=>(int)$a['starts_at']);
$icon=fn($name)=>$this->getObject('iconservice','ui')->render($name,['decorative'=>true]);
?>
<header class="chisimba-page-heading"><div><p class="chisimba-eyebrow"><?=$t('in_person_event')?></p><h1><?=$esc($e['title'])?></h1><p><?=$esc($e['summary'])?></p></div>
<div class="chisimba-form-actions chisimba-form-actions--equal"><?=$this->getObject('contextualhelp','help')->show('events','booking',true)?></div></header>
<div class="chisimba-publishing-layout"><div class="chisimba-composition-editor">
<section class="chisimba-form-card chisimba-form-card--wide chisimba-form-card--compact">
<?php if($d['image_url']): ?><img class="chisimba-publication-card__image" src="<?=$esc($d['image_url'])?>" alt="<?=$esc($d['image_alt']??$e['title'])?>"><?php endif; ?>
<h2><?=$t('about_event')?></h2><p><?=nl2br($esc($e['description']))?></p>
<?php if(!empty($d['public_area'])): ?><p><strong><?=$t('event_area')?>:</strong> <?=$esc($d['public_area'])?></p><?php endif; ?>
<div class="chisimba-form-actions chisimba-form-actions--equal"><a class="button" href="#event-dates"><?=$icon('calendar-days')?> <?=$t('choose_date')?></a><a class="button chisimba-button-secondary" href="<?=$url('catalogue')?>"><?=$icon('calendar-days')?> <?=$t('all_events')?></a></div>
</section>
<section id="event-dates" class="chisimba-composition-editor"><header><h2><?=$t('available_dates')?></h2><p><?=$t('choose_date_hint')?></p></header>
<?php if(!$upcoming): ?><p class="chisimba-notice"><?=$t('no_upcoming')?></p><?php endif; ?>
<?php foreach($upcoming as $o): $fieldPrefix='booking-'.$o['id'].'-'; $draft=($eventDraft['occurrence_id']??'')===$o['id']?$eventDraft:[]; ?>
<section class="chisimba-form-card chisimba-form-card--wide chisimba-form-card--compact">
<h3><?=$esc($eventService->date($o['starts_at'],$o['timezone']))?></h3><p><?=$esc($o['timezone'])?> · <?=$t($o['status']==='open'&&time()>=(int)$o['closes_at']?'closed':$o['status'])?><?php if($o['status']==='open'&&time()<(int)$o['closes_at']): ?> · <?=$esc($o['remaining'])?> <?=$t('places_remaining')?><?php endif; ?></p>
<?php if($o['price']): ?><p><?=$esc($o['price']['currency'])?> <?=number_format($o['price']['amount_minor']/100,2)?> <?=$t('per_ticket')?></p><?php endif; ?>
<?php if($o['status']==='open'&&time()<(int)$o['closes_at']): ?>
<?php if($o['remaining']>0||$eventOffer!==''): ?>
<details class="chisimba-form-disclosure"<?=!empty($draft)?' open':''?>><summary class="button"><?=$icon('ticket')?> <?=$t('book_this_date')?></summary><form class="chisimba-form" method="post" action="<?=$url('book')?>">
<?php $csrf(); $hidden('occurrence_id',$o['id']); $hidden('request_key',$eventRequestKey); $hidden('offer',$eventOffer); $hidden('campaign',$eventCampaign); ?>
<div class="chisimba-form-grid"><?php $field('name',$draft['name']??'','text',true); $field('email',$draft['email']??'','email',true); ?></div><?php $field('quantity',$draft['quantity']??1,'number',true); $field('attendees',$draft['attendees']??'','textarea',true,3); ?>
<p><?=$t('attendees_hint')?></p><?php if(!empty($d['terms'])): ?><details class="chisimba-form-disclosure"><summary><?=$t('terms')?></summary><p><?=nl2br($esc($d['terms']))?></p></details><?php endif; ?><label><input type="checkbox" name="accept_terms" value="1" required<?=!empty($draft['accept_terms'])?' checked':''?>> <?=$t('accept_terms')?></label>
<div class="chisimba-form-actions chisimba-form-actions--equal"><button type="submit" class="button"><?=$t('reserve')?></button></div></form></details>
<?php else: ?><p><?=$t('sold_out')?></p>
<details class="chisimba-form-disclosure"><summary class="button chisimba-button-secondary"><?=$t('join_waitlist')?></summary><form class="chisimba-form" method="post" action="<?=$url('waitlist')?>">
<?php $csrf(); $hidden('occurrence_id',$o['id']); $field('name','','text',true); $field('email','','email',true); ?>
<div class="chisimba-form-actions chisimba-form-actions--equal"><button type="submit" class="button"><?=$t('join_waitlist')?></button></div></form></details>
<?php endif; ?>
<?php endif; ?></section><?php endforeach; ?>
</section>
<section class="chisimba-form-card chisimba-form-card--wide chisimba-form-card--compact"><h2><?=$t('prepare_visit')?></h2>
<dl><?php foreach(['difficulty','difficulty_notes','bring','included','accessibility','weather'] as $key): if(empty($d[$key]))continue; ?>
<dt><strong><?=$t($key)?></strong></dt><dd><p><?=nl2br($esc($d[$key]))?></p></dd><?php endforeach; ?></dl>
<p><?=$t('location_private')?></p></section>
<?php if(!empty($d['terms'])): ?><section id="event-terms" class="chisimba-form-card chisimba-form-card--wide chisimba-form-card--compact"><h2><?=$t('terms')?></h2><p><?=nl2br($esc($d['terms']))?></p></section><?php endif; ?>
<?php if(!empty($d['gallery'])): ?><section class="chisimba-form-card chisimba-form-card--wide chisimba-form-card--compact"><h2><?=$t('event_photos')?></h2><div class="chisimba-publication-card-grid"><?php foreach($d['gallery'] as $photo): ?><img class="chisimba-publication-card__image" src="<?=$esc($photo)?>" alt="<?=$esc($e['title'])?>" loading="lazy"><?php endforeach; ?></div></section><?php endif; ?>
<?php if($past): ?><details class="chisimba-form-card chisimba-form-card--wide chisimba-form-card--compact"><summary><?=$t('past_dates')?></summary><p><?=$t('past_dates_hint')?></p><ul><?php foreach($past as $o): ?><li><?=$esc($eventService->date($o['starts_at'],$o['timezone']))?> · <?=$esc($o['timezone'])?> · <?=$t($o['status'])?></li><?php endforeach; ?></ul></details><?php endif; ?>
<?php if($e['reviews']): ?><h2><?=$t('reviews')?></h2><?php foreach($e['reviews'] as $r): ?>
<article class="chisimba-form-card"><p><?=$esc($r['rating'])?> / 5 · <?=$esc($r['display_name']?:$eventService->text('anonymous'))?> · <?=$t('verified_attendance')?></p>
<p><?=nl2br($esc($r['comment']))?></p><?php if($r['reply']): ?><p><strong><?=$t('organiser_reply')?></strong> <?=nl2br($esc($r['reply']))?></p><?php endif; ?></article>
<?php endforeach; endif; ?>
</div><aside class="chisimba-publishing-sidebar">
<?php if(!empty($d['host'])): ?><section class="chisimba-form-card chisimba-form-card--wide chisimba-form-card--compact"><h2><?=$t('meet_host')?></h2>
<?php if(!empty($d['host_image_url'])): ?><img src="<?=$esc($d['host_image_url'])?>" alt="<?=$esc($d['host'])?>" width="160" loading="lazy"><?php endif; ?>
<p><strong><?=$esc($d['host'])?></strong></p><?php if(!empty($d['host_biography'])): ?><p><?=nl2br($esc($d['host_biography']))?></p><?php endif; ?></section><?php endif; ?>
<?php if(!empty($d['contact'])): ?><section class="chisimba-form-card chisimba-form-card--wide chisimba-form-card--compact"><h2><?=$t('event_questions')?></h2><p><?=nl2br($esc($d['contact']))?></p></section><?php endif; ?>
<section class="chisimba-form-card chisimba-form-card--wide chisimba-form-card--compact"><h2><?=$t('share')?></h2><p><?=$t('share_hint')?></p><div class="chisimba-form-actions chisimba-form-actions--equal">
<button type="button" class="button chisimba-button-secondary" data-share-event data-share-title="<?=$esc($e['title'])?>" data-share-text="<?=$esc($e['summary'])?>" data-share-url="<?=$url('view',['id'=>$e['id']])?>"><?=$icon('share')?> <?=$t('share')?></button>
<button type="button" class="button chisimba-button-secondary" data-copy-event><?=$icon('copy')?> <?=$t('copy_promotion')?></button></div><p role="status" data-share-status data-copied="<?=$t('copied')?>" data-copy-failed="<?=$t('copy_failed')?>"></p></section>
</aside></div>
