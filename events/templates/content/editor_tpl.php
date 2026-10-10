<?php require __DIR__.'/common.php'; $r=$eventRecord; $values=isset($r['details'])?array_merge($r,$eventService->details($r)):$r; if(is_array($values['gallery']??null)) $values['gallery']=implode("\n",$values['gallery']); ?>
<header class="chisimba-page-heading"><div><p class="chisimba-eyebrow"><?=$t('event_setup')?></p><h1><?=$t(empty($r['id'])?'new_event':'edit_event')?></h1><p><?=$t('editor_intro')?></p></div>
<?=$this->getObject('contextualhelp','help')->show('events','organising',true)?></header>
<form class="chisimba-form chisimba-form--wide" data-event-editor method="post" action="<?=$url('saveevent')?>">
<?php $csrf(); $hidden('id',$r['id']??''); $hidden('create_id',$r['create_id']??bin2hex(random_bytes(16))); $hidden('revision',$r['revision']??0); ?>
<button type="submit" hidden tabindex="-1" aria-hidden="true"><?=$t('save_details')?></button>
<div class="chisimba-publishing-layout">
<div class="chisimba-composition-editor">
<section id="event-story" class="chisimba-form-card chisimba-form-card--wide chisimba-form-card--compact">
<header class="chisimba-form-card__header"><h2><?=$t('story_heading')?></h2><p><?=$t('story_hint')?></p></header>
<?php $field('title',$values['title']??'','text',true,3,'title_hint'); $field('summary',$values['summary']??'','textarea',true,2,'summary_hint'); $field('description',$values['description']??'','textarea',true,7,'description_hint'); ?>
</section>
<section id="event-images" class="chisimba-form-card chisimba-form-card--wide chisimba-form-card--compact">
<h2><?=$t('images_heading')?></h2><p><?=$t('event_image_hint')?></p>
<img class="chisimba-featured-image-preview" data-event-image-preview<?=empty($values['image_url'])?' hidden':' src="'.$esc($values['image_url']).'"'?> alt="<?=$esc($values['image_alt']??'')?>">
<?php $fieldPrefix='comp_'; $field('image_url',$values['image_url']??'','url'); $fieldPrefix=''; ?>
<div class="chisimba-form-actions chisimba-form-actions--equal"><button type="button" class="button chisimba-button-secondary" data-composition-media="comp_image_url"><?=$this->getObject('iconservice','ui')->render('image',['decorative'=>true])?> <?=$t('choose_image')?></button><button type="button" class="button chisimba-button-secondary" data-event-image-clear><?=$t('remove_image')?></button></div>
<?php $field('image_alt',$values['image_alt']??'','text',false,3,'image_alt_hint'); ?>
<details class="chisimba-form-disclosure"><summary><?=$t('more_photos')?></summary><?php $field('gallery',$values['gallery']??'','textarea',false,3); ?></details>
</section>
<?php $picker=html_entity_decode($this->uri(['action'=>'filepicker'],'filemanager'),ENT_QUOTES,'UTF-8'); $this->appendArrayVar('headerParams','<script>window.ChisimbaCompositionPicker='.json_encode($picker,JSON_HEX_TAG|JSON_HEX_AMP).';</script><script defer src="'.$esc($this->getResourceUri('composition.js','contentblocks')).'"></script>'); ?>
<section id="event-host" class="chisimba-form-card chisimba-form-card--wide chisimba-form-card--compact"><h2><?=$t('host_area_heading')?></h2><p><?=$t('host_area_hint')?></p>
<fieldset><legend><?=$t('host')?></legend>
<div class="chisimba-form-field"><label for="host_reference"><?=$t('host_reference')?></label><select id="host_reference" name="host_reference"><option value=""><?=$t('choose_host')?></option>
<?php foreach($this->getObject('hostservice','host-service')->choices() as $host): ?><option value="<?=$esc($host['id'])?>"<?=($values['host_reference']??'')===$host['id']?' selected':''?>><?=$esc($host['name'])?></option><?php endforeach; ?></select></div>
<?php if(!empty($values['host'])||!empty($values['host_user_id'])): $field('host',$values['host']??''); $hidden('host_user_id',$values['host_user_id']??''); ?><p><?=$t('legacy_host_hint')?></p><?php endif; ?>
<details<?=!empty($values['host_name'])?' open':''?>><summary><?=$t('new_host')?></summary>
<p><?=$t('new_host_hint')?></p>
<?php $hidden('host_create_id',$values['host_create_id']??bin2hex(random_bytes(16))); $field('host_name',$values['host_name']??''); $field('host_biography',$values['host_biography']??'','textarea'); $field('host_image_url',$values['host_image_url']??'','url'); ?>
<button class="button" type="submit" formaction="<?=$url('savehost')?>" formnovalidate><?=$t('save_host')?></button>
</details></fieldset>
<?php $field('public_area',$values['public_area']??'','text',false,3,'public_area_hint'); $field('contact',$values['contact']??'','text',false,3,'contact_hint'); ?>
</section>
<section id="event-preparation" class="chisimba-form-card chisimba-form-card--wide chisimba-form-card--compact"><h2><?=$t('preparation_heading')?></h2><p><?=$t('preparation_hint')?></p>
<div class="chisimba-form-grid">
<?php $difficulty=$values['difficulty']??''; $choices=['','easy','moderate','strenuous']; $select('difficulty',$choices,in_array($difficulty,$choices,true)?$difficulty:'');
$field('difficulty_notes',$values['difficulty_notes']??(in_array($difficulty,$choices,true)?'':$difficulty),'textarea',false,2);
$field('bring',$values['bring']??'','textarea',false,3); $field('included',$values['included']??'','textarea',false,3); ?>
</div>
<details class="chisimba-form-disclosure"<?=!empty($values['accessibility'])||!empty($values['weather'])?' open':''?>><summary><?=$t('access_weather')?></summary>
<?php $field('accessibility',$values['accessibility']??'','textarea',false,2); $field('weather',$values['weather']??'','textarea',false,2); ?></details>
</section>
<details class="chisimba-form-card chisimba-form-card--wide chisimba-form-card--compact"<?=!empty($values['followup'])?' open':''?>>
<summary><?=$t('followup_heading')?></summary><p><?=$t('followup_hint')?></p>
<?php $field('followup',$values['followup']??'','textarea',false,5); ?></details>
<details class="chisimba-form-card chisimba-form-card--wide chisimba-form-card--compact"<?=!empty($values['terms'])?' open':''?>>
<summary><?=$t('terms_override')?></summary><p><?=$t('terms_hint')?></p><?php $field('terms',$values['terms']??'','textarea',false,5); ?></details>
<div class="chisimba-form-actions chisimba-form-actions--equal"><button type="submit" class="button" name="next_step" value="date"><?=$t('save_continue')?></button><button type="submit" class="button chisimba-button-secondary"><?=$t('save_details')?></button></div>
</div>
<aside class="chisimba-publishing-sidebar">
<section class="chisimba-form-card chisimba-form-card--wide chisimba-form-card--compact"><h2><?=$t('setup_steps')?></h2>
<ol><li><strong><?=$t('step_details')?></strong><p><?=$t('step_details_hint')?></p></li><li><?=$t('step_date')?><p><?=$t('step_date_hint')?></p></li><li><?=$t('step_publish')?><p><?=$t('step_publish_hint')?></p></li></ol>
<?php $select('status',['draft','published','archived'],$r['status']??'draft'); ?><p class="chisimba-field-help"><?=$t('publish_hint')?></p>
<div class="chisimba-form-actions chisimba-form-actions--equal"><button type="submit" class="button" name="next_step" value="date"><?=$t('save_continue')?></button></div>
<p class="chisimba-save-state" data-editor-state data-dirty="<?=$t('unsaved_changes')?>"><?=$t(($eventNotice??'')==='saved'?'saved':'editing_state')?></p>
<div class="chisimba-form-actions chisimba-form-actions--equal">
<?php if(!empty($r['id'])&&($r['status']??'')==='published'): ?><a class="button chisimba-button-secondary" target="_blank" rel="noopener" href="<?=$url('view',['id'=>$r['id']])?>"><?=$t('preview_public')?></a><?php endif; ?>
<a class="button chisimba-button-secondary" href="<?=$url('manage')?>"><?=$t('manage')?></a></div>
</section>
<nav class="chisimba-form-card chisimba-form-card--wide chisimba-form-card--compact" aria-label="<?=$t('on_this_page')?>"><h2><?=$t('on_this_page')?></h2><ul><li><a href="#event-story"><?=$t('story_heading')?></a></li><li><a href="#event-host"><?=$t('host_area_heading')?></a></li><li><a href="#event-preparation"><?=$t('preparation_heading')?></a></li><li><a href="#event-images"><?=$t('images_heading')?></a></li></ul></nav>
<?php if($this->getObject('user','security')->isAdmin()): ?><section class="chisimba-form-card chisimba-form-card--wide chisimba-form-card--compact"><h2><?=$t('defaults_heading')?></h2><p><?=$t('defaults_hint')?></p><a class="button chisimba-button-secondary" target="_blank" rel="noopener" href="<?=$esc($this->uri(['action'=>'step2','pmodule_id'=>'events'],'sysconfig'))?>"><?=$t('settings')?></a></section><?php endif; ?>
</aside></div></form>
