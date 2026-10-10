<?php
/** Public event cards reuse the same skin primitives as Webinar.
 * @author Derek Keats <derek@dkeats.com>
 */
$e=$card['event'];$date=$card['date'];$d=$e['details'];
$when=$date?(new DateTimeImmutable('@'.(int)$date['starts_at']))->setTimezone(new DateTimeZone($date['timezone'])):null;
?>
<article class="chisimba-publication-card">
<a class="chisimba-publication-card__media" href="<?=$url('view',['id'=>$e['id']])?>" aria-label="<?=$esc($e['title'])?>">
<?php if(!empty($d['image_url'])): ?><img class="chisimba-publication-card__image" src="<?=$esc($d['image_url'])?>" alt="" loading="lazy"><?php else: ?><div class="chisimba-publication-card__placeholder"><?=$this->getObject('iconservice','ui')->render('calendar-days',['decorative'=>true])?></div><?php endif; ?>
<?php if($when): ?><time class="chisimba-publication-card__calendar" datetime="<?=$esc($when->format('Y-m-d'))?>" aria-label="<?=$esc($when->format('j F Y'))?>"><strong><?=$esc($when->format('j'))?></strong><span><?=$esc($when->format('M'))?></span></time><?php endif; ?></a>
<div class="chisimba-publication-card__body"><h3><a href="<?=$url('view',['id'=>$e['id']])?>"><?=$esc($e['title'])?></a></h3>
<?php if(!empty($d['host'])): ?><p class="chisimba-publication-card__byline"><?=$this->getObject('iconservice','ui')->render('user',['decorative'=>true])?> <?=$esc($d['host'])?></p><?php endif; ?>
<p><?=$esc($e['summary'])?></p>
<?php if(!empty($d['public_area'])): ?><p><?=$esc($d['public_area'])?></p><?php endif; ?>
<p><?=$date?$esc($eventService->date($date['starts_at'],$date['timezone']).' · '.$date['timezone']):$t('dates_soon')?></p>
<?php if($date&&$section==='upcoming'): ?>
<p><?=$t($date['status'])?><?php if($date['status']==='open'): ?> · <?=$esc($date['remaining'])?> <?=$t('places_remaining')?><?php endif; ?></p>
<?php if($date['price']): ?><p><?=$esc($date['price']['currency'])?> <?=number_format($date['price']['amount_minor']/100,2)?> <?=$t('per_ticket')?></p><?php endif; ?>
<?php elseif($date): ?><p class="chisimba-field-help"><?=$t('past_card_note')?></p><?php endif; ?>
<div class="chisimba-publication-card__actions"><a class="button" href="<?=$url('view',['id'=>$e['id']])?>"><?=$t($section==='past'?'view_past':'view_event')?></a></div>
</div></article>
