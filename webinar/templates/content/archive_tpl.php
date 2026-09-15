<?php
/** Public archive journey using shared skin cards and icon actions. */
$r=$this->getObject('webinarrenderer','webinar');
$e=['webinarrenderer','escape'];
if (!$this->getObject('navigationservice','toolbar')->provides('webinar',array('upcoming','archive','recordings'))) echo '<nav class="chisimba-form-actions">'.$r->button('title','calendar',['action'=>'upcoming']).$r->button('archive_nav','archive',['action'=>'archive']).$r->button('recordings','play',['action'=>'recordings']).$r->button('speakers','users',['action'=>'speakers']).'</nav>';
if($this->getVar('webinarMissing')){echo '<p>'.$e($r->text('notfound')).'</p>';return;}
$record=$this->getVar('webinarRecord');
if($record){$booking='';if($this->getVar('webinarInlineBooking')){ob_start();include __DIR__.'/booking_tpl.php';$booking=ob_get_clean();}echo $r->detail($record,$booking);return;}
$kind=$this->getVar('webinarAction')==='speakers'?'speaker':'webinar';
echo '<h1>'.$e($r->text($kind==='speaker'?'speakers':($this->getVar('webinarAction')==='recordings'?'recordings':($this->getVar('webinarAction')==='archive'?'archive':'title')))).'</h1>';
$rows=$this->getObject('webinarstore','webinar')->published($kind);
if($this->getVar('webinarAction')==='recordings')$rows=array_filter($rows,fn($row)=>!empty($r->data($row)['recording']));
if($kind==='webinar'&&$this->getVar('webinarAction')!=='recordings')$rows=webinarschedule::catalogue($rows,$this->getVar('webinarAction')==='archive');
$pages=max(1,(int)ceil(count($rows)/12));
$page=filter_var($this->getParam('page',1),FILTER_VALIDATE_INT);$page=max(1,min($pages,$page===false?1:$page));
$rows=array_slice(array_values($rows),($page-1)*12,12);
echo $rows?$r->cards($rows):'<p>'.$e($r->text($this->getVar('webinarAction')==='upcoming'?'empty_upcoming':'empty')).'</p>';

if($pages>1){
 echo '<nav class="chisimba-form-actions" aria-label="'.$e($r->text('pagination')).'">';
 if($page>1)echo $r->button('previous','chevron-left',['action'=>$this->getVar('webinarAction'),'page'=>$page-1]);
 echo '<span>'. $e(sprintf($r->text('page_count'),$page,$pages)).'</span>';
 if($page<$pages)echo $r->button('next','chevron-right',['action'=>$this->getVar('webinarAction'),'page'=>$page+1]);
 echo '</nav>';
}
