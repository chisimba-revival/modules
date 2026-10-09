<?php
/** Staff overview retains simple native links and the existing card system. */
$r=$this->getObject('webinarrenderer','webinar');$e=['webinarrenderer','escape'];$kind=$this->getVar('webinarEditKind');
// Use the existing Audience permission boundary; opening this link never queues mail.
$newsletter='';
if($this->getObject('modules','modulecatalogue')->checkIfRegistered('audience')&&$this->getObject('audienceadmin','audience')->allowed()){
 $newsletter='<a class="button" href="'.$e($this->uri(['action'=>'campaigns'],'audience')).'">'.$this->getObject('iconservice','ui')->render('mail',['decorative'=>true]).'<span>'.$e($r->text('newsletters')).'</span></a>';
}
echo '<section class="chisimba-form-card chisimba-form-card--wide"><header class="chisimba-form-actions"><h1>'.$e($r->text($kind==='speaker'?'manage_speakers':'manage')).'</h1>'.$this->getObject('contextualhelp','help')->show('webinar','editing',true).'</header><nav class="chisimba-form-actions">'.$r->button($kind==='speaker'?'add_speaker':'add','plus',['action'=>'edit','kind'=>$kind]).$r->button($kind==='speaker'?'manage':'manage_speakers','list',['action'=>'manage','kind'=>$kind==='speaker'?'webinar':'speaker']).$r->button('title','calendar',['action'=>'upcoming']).$newsletter.'</nav>';
$trashed=$kind==='webinar'&&$this->getParam('trashed')==='1';
if($kind==='webinar')echo '<nav class="chisimba-form-actions">'.$r->button($trashed?'manage':'trash_list','list',['action'=>'manage','kind'=>'webinar','trashed'=>$trashed?'0':'1']).'</nav><p>'.$e($r->text('trash_help')).'</p>';
if($this->getVar('webinarRemovalError'))echo '<p role="alert">'.$e($r->text($this->getVar('webinarRemovalError'))).'</p>';
$iconLink=function($label,$icon,$action,$row)use($r,$e){return '<a class="chisimba-icon-button" href="'.$e($this->uri(['action'=>$action,'id'=>$row['id']],'webinar')).'" aria-label="'.$e($r->text($label).' '.$row['title']).'" title="'.$e($r->text($label)).'">'.$this->getObject('iconservice','ui')->render($icon,['decorative'=>true]).'</a>';};
$removeButton=function($row)use($r,$e,$trashed){
 if($row['kind']!=='webinar')return '';
 $label=$trashed?'restore_draft':'trash';$action=$trashed?'restore':'trash';
 return '<form method="post" action="'.$e($this->uri(['action'=>$action],'webinar')).'" onsubmit="return confirm('.$e(json_encode($r->text($trashed?'restore_confirm':'trash_confirm'))).')"><input type="hidden" name="id" value="'.$e($row['id']).'"><input type="hidden" name="version" value="'.$e(webinareditservice::version($row)).'"><input type="hidden" name="csrf_token" value="'.$e($this->getVar('webinarEditCsrf')).'"><button class="chisimba-icon-button'.($trashed?'':' chisimba-button-danger').'" type="submit" aria-label="'.$e($r->text($label).' '.$row['title']).'" title="'.$e($r->text($label)).'">'.$this->getObject('iconservice','ui')->render($trashed?'rotate-ccw':'trash-2',['decorative'=>true]).'</button></form>';
};
$rows=$this->getObject('webinareditstore','webinar')->listing($kind);$rows=array_values(array_filter($rows,fn($row)=>($row['status']==='trashed')===$trashed));
$group='';
if($kind==='webinar'&&!$trashed){
 $this->getObject('webinarmanagement','webinar');$groups=webinarmanagement::groups($rows);$group=(string)$this->getParam('group','upcoming');if(!isset($groups[$group]))$group='upcoming';
 echo '<nav class="chisimba-form-actions" aria-label="'.$e($r->text('manage_groups')).'">';
 foreach($groups as $key=>$items)echo '<a class="button'.($key===$group?'':' chisimba-button-secondary').'"'.($key===$group?' aria-current="page"':'').' href="'.$e($this->uri(['action'=>'manage','group'=>$key],'webinar')).'">'.$e($r->text('manage_'.$key)).' ('.count($items).')</a>';
 echo '</nav><h2>'.$e($r->text('manage_'.$group)).'</h2><p>'.$e($r->text('manage_order_help')).'</p>';$rows=$groups[$group];
 if(!$rows)echo '<p>'.$e($r->text('manage_empty')).'</p>';
}
$pages=max(1,(int)ceil(count($rows)/12));$page=max(1,min($pages,(int)$this->getParam('page',1)));
echo '<div class="chisimba-publication-card-grid">';foreach(array_slice($rows,($page-1)*12,12) as $row)echo '<article class="chisimba-publication-card"><div class="chisimba-publication-card__body"><h2>'.$e(html_entity_decode($row['title'],ENT_QUOTES|ENT_HTML5,'UTF-8')).'</h2><p>'.$e($r->text($trashed?'trash_list':($kind==='webinar'?webinarmanagement::status($row):($row['status']==='published'?'editor_published':'editor_draft')))).'</p>'.$r->date($row).'<div class="chisimba-form-actions">'.($trashed?'':$iconLink('edit','pencil','edit',$row).$iconLink('preview','eye','preview',$row)).$removeButton($row).'</div></div></article>';echo '</div><nav class="chisimba-form-actions">';if($page>1)echo $r->button('previous','chevron-left',['action'=>'manage','kind'=>$kind,'page'=>$page-1,'group'=>$group,'trashed'=>$trashed?'1':'0']);if($page<$pages)echo $r->button('next','chevron-right',['action'=>'manage','kind'=>$kind,'page'=>$page+1,'group'=>$group,'trashed'=>$trashed?'1':'0']);echo '</nav></section>';
