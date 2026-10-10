<?php require __DIR__.'/common.php'; $sections=$eventService->catalogueSections($eventPublicRecords); ?>
<header class="chisimba-page-heading"><div><h1><?=$t('heading')?></h1><p><?=$t('listing_intro')?></p></div>
<div class="chisimba-form-actions chisimba-form-actions--equal">
<?php if($eventPolicy->canCreate()): ?><a class="button" href="<?=$url('manage')?>"><?=$t('manage')?></a><?php endif; ?>
<?php if($this->getObject('user','security')->isLoggedIn()): ?><a class="button chisimba-button-secondary" href="<?=$url('checkins')?>"><?=$t('check_tickets')?></a><?php endif; ?>
<?=$this->getObject('contextualhelp','help')->show('events','booking',true)?></div></header>
<nav class="chisimba-form-actions" aria-label="<?=$t('on_this_page')?>"><a href="#upcoming-events"><?=$t('upcoming_heading')?></a><a href="#past-events"><?=$t('past_heading')?></a></nav>
<?php foreach($sections as $section=>$cards): ?>
<section class="chisimba-form-section" id="<?=$esc($section)?>-events" aria-labelledby="<?=$esc($section)?>-heading"><h2 id="<?=$esc($section)?>-heading"><?=$t($section.'_heading')?></h2>
<?php if(!$cards): ?><p><?=$t('no_'.$section)?></p><?php else: ?>
<div class="chisimba-publication-card-grid"><?php foreach($cards as $card): require __DIR__.'/cataloguecard_tpl.php'; endforeach; ?></div>
<?php endif; ?></section><?php endforeach; ?>
