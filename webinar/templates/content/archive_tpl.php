<?php
/** Public archive journey using shared skin cards and icon actions. */
$r=$this->getObject('webinarrenderer','webinar');
$e=['webinarrenderer','escape'];
if (!$this->getObject('navigationservice','toolbar')->provides('webinar',array('archive','recordings','speakers'))) echo '<nav class="chisimba-form-actions">'.$r->button('title','calendar',['action'=>'archive']).$r->button('recordings','play',['action'=>'recordings']).$r->button('speakers','users',['action'=>'speakers']).'</nav>';
echo '<details class="chisimba-form-section"><summary>'.$e($r->text('help')).'</summary><p>'.$e($r->text('helpbody')).'</p></details>';
if($this->getVar('webinarMissing')){echo '<p>'.$e($r->text('notfound')).'</p>';return;}
$record=$this->getVar('webinarRecord');
if($record){$booking='';if($this->getVar('webinarInlineBooking')){ob_start();include __DIR__.'/booking_tpl.php';$booking=ob_get_clean();}echo $r->detail($record,$booking);return;}
$kind=$this->getVar('webinarAction')==='speakers'?'speaker':'webinar';
echo '<h1>'.$e($r->text($kind==='speaker'?'speakers':($this->getVar('webinarAction')==='recordings'?'recordings':'title'))).'</h1>';
$rows=$this->getObject('webinarstore','webinar')->published($kind);
if($this->getVar('webinarAction')==='recordings')$rows=array_filter($rows,fn($row)=>!empty($r->data($row)['recording']));
if($kind==='webinar'&&$this->getVar('webinarAction')!=='recordings'){
 usort($rows,function($a,$b){$now=time();$x=webinarschedule::start($a);$y=webinarschedule::start($b);$x=$x?$x->getTimestamp():0;$y=$y?$y->getTimestamp():0;if(($x>$now)!==($y>$now))return $x>$now?-1:1;return $x>$now?$x<=>$y:$y<=>$x;});
}
echo $rows?$r->cards($rows):'<p>'.$e($r->text('empty')).'</p>';
