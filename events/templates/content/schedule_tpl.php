<?php require __DIR__.'/common.php'; $o=$eventOccurrence; $v=$o;
if(isset($o['private_details'])) {
$v=array_merge($o,$eventService->details($o,'private_details'),$eventService->details($o)); $v['helpers']=implode(', ',$v['helpers']??[]);
foreach(['starts_at','ends_at','closes_at'] as $key) $v[$key]=(new DateTimeImmutable('@'.(int)$o[$key]))->setTimezone(new DateTimeZone($o['timezone']))->format('Y-m-d\TH:i');
$p=$this->getObject('paymentcatalogservice','payment-service')->productVersion($o['product_code'],$o['price_version']); $v=array_merge($v,$p['price']??[]);
}
?>
<header class="chisimba-page-heading"><div><p class="chisimba-eyebrow"><?=$t('event_setup')?></p><h1><?=$t('step_date')?></h1><p><?=$t('date_intro')?></p></div><?=$this->getObject('contextualhelp','help')->show('events','organising',true)?></header>
<form class="chisimba-form chisimba-form--wide" data-event-editor method="post" action="<?=$url('saveschedule')?>">
<?php $csrf(); $hidden('id',$o['id']??''); $hidden('create_id',$o['create_id']??bin2hex(random_bytes(16))); $hidden('event_id',$o['event_id']??''); $hidden('revision',$o['revision']??0); ?>
<div class="chisimba-publishing-layout"><div class="chisimba-composition-editor">
<section class="chisimba-form-card chisimba-form-card--wide chisimba-form-card--compact"><h2><?=$t('when_heading')?></h2>
<div class="chisimba-form-grid"><?php foreach(['starts_at','ends_at'] as $key) $field($key,$v[$key]??'','datetime-local',true); ?></div>
<?php $field('closes_at',$v['closes_at']??'','datetime-local',true,3,'closes_hint'); ?>
<div class="chisimba-form-field"><label for="timezone"><?=$t('timezone')?></label><select name="timezone" id="timezone">
<?php $zone=$v['timezone']??$this->getObject('timeanddateservice','timeanddate-service')->siteTimezone(); foreach(DateTimeZone::listIdentifiers() as $choice): ?><option value="<?=$esc($choice)?>"<?=$choice===$zone?' selected':''?>><?=$esc(str_replace('_',' ',$choice))?></option><?php endforeach; ?></select></div>
</section>
<section class="chisimba-form-card chisimba-form-card--wide chisimba-form-card--compact"><h2><?=$t('ticket_heading')?></h2><p><?=$t('ticket_price_hint')?></p>
<div class="chisimba-form-grid">
<?php $field('capacity',$v['capacity']??'','number',true); $field('price',$v['price']??(isset($v['amount_minor'])?number_format($v['amount_minor']/100,2,'.',''):''),'text',true,3,'price_hint'); ?>
</div>
<details class="chisimba-form-disclosure"<?=!empty($v['vat_minor'])||!empty($v['vat_percent'])?' open':''?>><summary><?=$t('tax_currency')?></summary><div class="chisimba-form-grid">
<?php $field('vat_percent',$v['vat_percent']??(isset($v['amount_minor'])?$eventService::vatPercentage($v['amount_minor'],$v['vat_minor']??0):'0'),'text',true,3,'vat_percent_hint'); $field('currency',$v['currency']??'ZAR','text',true); ?></div><p class="chisimba-field-help"><?=$t('vat_component')?>: <output data-vat-component><?=isset($v['vat_minor'])?$esc(number_format($v['vat_minor']/100,2,'.','')):'—'?></output>. <?=$t('vat_total_unchanged')?></p></details>
</section>
<section class="chisimba-form-card chisimba-form-card--wide chisimba-form-card--compact"><header class="chisimba-form-card__header"><p class="chisimba-eyebrow"><?=$t('ticket_holders_only')?></p><h2><?=$t('private_arrival')?></h2><p><?=$t('location_private')?></p></header>
<?php $field('meeting',$v['meeting']??'','textarea',false,2,'meeting_hint'); $field('directions',$v['directions']??'','textarea',false,4); $field('parking',$v['parking']??'','textarea',false,2); ?>
</section>
<details class="chisimba-form-card chisimba-form-card--wide chisimba-form-card--compact"<?=!empty($v['helpers'])?' open':''?>><summary><?=$t('arrival_team')?></summary><p><?=$t('arrival_team_hint')?></p>
<div class="chisimba-form-field"><label for="helper_ids"><?=$t('helpers_picker')?></label><select name="helper_ids[]" id="helper_ids" multiple size="4">
<?php $selected=array_filter(array_map('trim',explode(',',$v['helpers']??''))); foreach($this->getObject('userservice','security')->listUsers() as $person): ?><option value="<?=$esc($person['userid'])?>"<?=in_array($person['userid'],$selected,true)?' selected':''?>><?=$esc(trim(($person['firstname']??'').' '.($person['surname']??''))?:$person['username'])?></option><?php endforeach; ?></select><small class="chisimba-field-help"><?=$t('helpers_hint')?></small></div>
<ol><li><?=$t('team_step_assign')?></li><li><?=$t('team_step_open')?></li><li><?=$t('team_step_scan')?></li></ol>
<?php if(!empty($o['id'])): ?><div class="chisimba-form-actions chisimba-form-actions--equal"><a class="button chisimba-button-secondary" target="_blank" rel="noopener" href="<?=$url('scan',['id'=>$o['id']])?>"><?=$t('team_checkin_link')?></a></div><?php else: ?><p><?=$t('team_link_after_save')?></p><?php endif; ?>
</details>
<details class="chisimba-form-card chisimba-form-card--wide chisimba-form-card--compact"<?=!empty($v['change_message'])?' open':''?>><summary><?=$t('change_notices_heading')?></summary><p><?=$t('change_notices_hint')?></p>
<?php $field('change_message',$v['change_message']??'','textarea',false,3); ?></details>
<div class="chisimba-form-actions chisimba-form-actions--equal"><button type="submit" class="button"><?=$t('save_date')?></button><a class="button chisimba-button-secondary" href="<?=$url('edit',['id'=>$o['event_id']??''])?>"><?=$t('edit_details')?></a></div>
</div><aside class="chisimba-publishing-sidebar"><section class="chisimba-form-card chisimba-form-card--wide chisimba-form-card--compact"><h2><?=$t('booking_status')?></h2>
<?php $select('status',['open','closed','cancelled'],$o['status']??'open'); ?><p class="chisimba-field-help"><?=$t('booking_status_hint')?></p>
<p class="chisimba-save-state" data-editor-state data-dirty="<?=$t('unsaved_changes')?>"><?=$t(($eventNotice??'')==='saved'?'saved':'editing_state')?></p>
<div class="chisimba-form-actions chisimba-form-actions--equal"><button type="submit" class="button"><?=$t('save_date')?></button>
<?php if(!empty($o['id'])): ?><a class="button chisimba-button-secondary" href="<?=$url('operations',['id'=>$o['id']])?>"><?=$t('operations')?></a><?php endif; ?>
<a class="button chisimba-button-secondary" href="<?=$url('edit',['id'=>$o['event_id']??''])?>"><?=$t('edit_details')?></a></div></section>
<section class="chisimba-form-card chisimba-form-card--wide chisimba-form-card--compact"><h2><?=$t('after_date_heading')?></h2><p><?=$t('after_date_hint')?></p><p><?=$t('cancel_hint')?></p></section>
</aside></div></form>
