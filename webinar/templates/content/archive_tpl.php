<?php
/** Public archive journey using shared skin cards and icon actions. */
$r=$this->getObject('webinarrenderer','webinar');
$e=['webinarrenderer','escape'];
echo '<nav class="chisimba-form-actions">'.$r->button('title','calendar',['action'=>'archive']).$r->button('recordings','play',['action'=>'recordings']).$r->button('speakers','users',['action'=>'speakers']).'</nav>';
echo '<details class="chisimba-form-section"><summary>'.$e($r->text('help')).'</summary><p>'.$e($r->text('helpbody')).'</p></details>';
if($this->getVar('webinarMissing')){echo '<p>'.$e($r->text('notfound')).'</p>';return;}
$record=$this->getVar('webinarRecord');
if($record){echo $r->detail($record);return;}
$kind=$this->getVar('webinarAction')==='speakers'?'speaker':'webinar';
echo '<h1>'.$e($r->text($kind==='speaker'?'speakers':($this->getVar('webinarAction')==='recordings'?'recordings':'title'))).'</h1>';
$rows=$this->getObject('webinarstore','webinar')->published($kind);
if($this->getVar('webinarAction')==='recordings')$rows=array_filter($rows,fn($row)=>!empty($r->data($row)['recording']));
echo $rows?$r->cards($rows):'<p>'.$e($r->text('empty')).'</p>';
