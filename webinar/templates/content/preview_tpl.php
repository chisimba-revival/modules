<?php
/** Authenticated saved preview, with a clear route back to editing. */
$r=$this->getObject('webinarrenderer','webinar');$record=$this->getVar('webinarEditRecord');
echo '<aside class="chisimba-guidance-card"><p>'.webinarrenderer::escape($r->text('preview_help')).'</p>'.$r->button('edit','pencil',['action'=>'edit','id'=>$record['id']]).$r->button('manage','list',['action'=>'manage']).'</aside>'.$r->detail($record);
