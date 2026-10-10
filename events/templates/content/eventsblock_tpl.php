<div class="chisimba-composition-editor">
<?php foreach($sections as $section=>$cards): ?>
<section class="chisimba-composition-editor">
<h3><?=$t($section.'_heading')?></h3>
<?php if(!$cards): ?><p><?=$t('no_'.$section)?></p><?php else: ?>
<div class="chisimba-publication-card-grid">
<?php foreach(array_slice($cards,0,3) as $card): require __DIR__.'/cataloguecard_tpl.php'; endforeach; ?>
</div>
<?php endif; ?></section>
<?php endforeach; ?>
<div class="chisimba-form-actions chisimba-form-actions--equal"><a class="button" href="<?=$url('catalogue')?>"><?=$this->getObject('iconservice','ui')->render('calendar-days',['decorative'=>true])?> <?=$t('all_events')?></a></div>
</div>
