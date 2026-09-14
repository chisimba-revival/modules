<?php
/** Registration and email-link actions share native forms and skin primitives. */
$r=$this->getObject('webinarrenderer','webinar');$e=['webinarrenderer','escape'];
echo '<nav class="chisimba-form-actions">'.$r->button('archive','video',['action'=>'archive']).'</nav><section class="chisimba-form-section">';
$notice=$this->getVar('webinarNotice');
if($notice){echo '<h1>'.$e($r->text($notice.'_title')).'</h1><p role="status">'.$e($r->text($notice.'_body')).'</p></section>';return;}
$action=$this->getVar('webinarFormAction');$record=$this->getVar('webinarRecord');$error=$this->getVar('webinarError');$input=$this->getVar('webinarInput');
echo '<h1>'.$e($r->text($action)).'</h1>';
if($record)echo '<h2>'.$e($record['title']).'</h2>'.$r->date($record);
if($error)echo '<p role="alert">'.$e($r->text($error)).'</p>';
if(in_array($error,['closed','expired'],true)){echo '</section>';return;}
include __DIR__.'/registration_form_tpl.php';
echo '</section>';
