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
echo '<form method="post" action="'.$e($this->uri(['action'=>$action],'webinar')).'" class="chisimba-flow">'
    .'<input type="hidden" name="csrf_token" value="'.$e($this->getVar('webinarCsrf')).'">';
if($action==='register'){
 echo '<input type="hidden" name="id" value="'.$e($record['id']).'">';
 foreach(['name'=>'text','email'=>'email'] as $field=>$type)echo '<div class="chisimba-form-field"><label for="webinar-'.$field.'">'.$e($r->text($field)).'</label><input id="webinar-'.$field.'" name="'.$field.'" type="'.$type.'" autocomplete="'.$field.'" maxlength="'.($field==='email'?254:200).'" value="'.$e($input[$field]).'" required></div>';
 echo '<div hidden aria-hidden="true"><label for="webinar-website">'.$e($r->text('website')).'</label><input id="webinar-website" name="website" tabindex="-1" autocomplete="off"></div>';
 echo '<div class="chisimba-form-field"><label><input type="checkbox" name="consent" value="1" required '.($input['consent']==='1'?'checked':'').'> '.$e($r->text('consent')).'</label></div><p>'.$e($r->text('registration_help')).'</p>';
}else{
 echo '<input type="hidden" name="token" value="'.$e($this->getVar('webinarToken')).'"><p>'.$e($r->text($action.'_help')).'</p>';
}
echo '<div class="chisimba-form-actions"><button type="submit" class="button chisimba-button-primary">'.$this->getObject('iconservice','ui')->render($action==='unsubscribe'?'mail-x':'check',['decorative'=>true]).'<span>'.$e($r->text($action)).'</span></button></div></form></section>';
