<?php
/** Public archive journey using shared skin cards and icon actions. */
$r=$this->getObject('webinarrenderer','webinar');
$e=['webinarrenderer','escape'];
if (!$this->getObject('navigationservice','toolbar')->provides('webinar',array('upcoming','archive','recordings'))) echo '<nav class="chisimba-form-actions">'.$r->button('title','calendar',['action'=>'upcoming']).$r->button('archive_nav','archive',['action'=>'archive']).$r->button('recordings','play',['action'=>'recordings']).$r->button('speakers','users',['action'=>'speakers']).'</nav>';
if($this->getVar('webinarMissing')){echo '<p>'.$e($r->text('notfound')).'</p>';return;}
if($this->getObject('webinareditpolicy','webinar')->canManage()){echo '<nav class="chisimba-form-actions">'.$r->button('add','plus',['action'=>'edit']).$r->button('manage','list',['action'=>'manage']);if($this->getObject('audienceadmin','audience')->allowed()){$ar=$this->getObject('audiencerenderer','audience');echo $ar->link('users','users',['action'=>'users']).$ar->link('campaigns','mail',['action'=>'campaigns']);}echo '</nav>';}
$record=$this->getVar('webinarRecord');
if($record){$booking='';if($this->getVar('webinarInlineBooking')){ob_start();include __DIR__.'/booking_tpl.php';$booking=ob_get_clean();}echo $r->detail($record,$booking);return;}
$kind=$this->getVar('webinarAction')==='speakers'?'speaker':'webinar';
echo '<h1>'.$e($r->text($kind==='speaker'?'speakers':($this->getVar('webinarAction')==='recordings'?'recordings':($this->getVar('webinarAction')==='archive'?'archive':'title')))).'</h1>';
$rows=$this->getObject('webinarstore','webinar')->published($kind);
if($this->getVar('webinarAction')==='recordings')$rows=array_filter($rows,fn($row)=>!empty($r->data($row)['recording']));
if($kind==='webinar'&&$this->getVar('webinarAction')!=='recordings')$rows=webinarschedule::catalogue($rows,$this->getVar('webinarAction')==='archive');
$category=is_string($this->getParam('category'))?$this->getParam('category'):'';$tag=is_string($this->getParam('tag'))?$this->getParam('tag'):'';
if($kind==='webinar'){[$rows,$filters]=$this->getObject('webinarclassificationui','webinar')->catalogue($rows,$this->getVar('webinarAction'),$category,$tag);echo $filters;}
$pages=max(1,(int)ceil(count($rows)/12));
$page=filter_var($this->getParam('page',1),FILTER_VALIDATE_INT);$page=max(1,min($pages,$page===false?1:$page));
$rows=array_slice(array_values($rows),($page-1)*12,12);
if($this->getVar('webinarAction')==='upcoming'&&$this->getObject('user','security')->isLoggedIn())echo $this->getObject('contextualhelp','help')->show('webinar','registration_counts',true);
echo $rows?$r->cards($rows,$this->getVar('webinarAction')==='upcoming'):'<p>'.$e($r->text($this->getVar('webinarAction')==='upcoming'?'empty_upcoming':'empty')).'</p>';

if($pages>1){
 echo '<nav class="chisimba-form-actions" aria-label="'.$e($r->text('pagination')).'">';
 if($page>1)echo $r->button('previous','chevron-left',['action'=>$this->getVar('webinarAction'),'page'=>$page-1,'category'=>$category,'tag'=>$tag]);
 echo '<span>'. $e(sprintf($r->text('page_count'),$page,$pages)).'</span>';
 if($page<$pages)echo $r->button('next','chevron-right',['action'=>$this->getVar('webinarAction'),'page'=>$page+1,'category'=>$category,'tag'=>$tag]);
 echo '</nav>';
}
