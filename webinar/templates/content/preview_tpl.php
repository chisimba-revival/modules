<?php
/** Authenticated saved preview, with a clear route back to editing. */
$r=$this->getObject('webinarrenderer','webinar');$record=$this->getVar('webinarEditRecord');
echo '<aside class="chisimba-guidance-card"><p>'.webinarrenderer::escape($r->text('preview_help')).'</p><nav class="chisimba-form-actions">'.$r->button('edit','pencil',['action'=>'edit','id'=>$record['id']]).$r->button('manage','list',['action'=>'manage']).'</nav></aside>'.$r->detail($record);
