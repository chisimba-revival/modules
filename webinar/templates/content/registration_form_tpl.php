<?php
/** Shared booking and email-action form, hosted by the owning page. */
echo '<form method="post" action="'.$e($this->uri($action==='register'?['action'=>'view','id'=>$record['id']]:['action'=>$action],'webinar')).($action==='register'?'#booking':'').'" class="chisimba-flow">'
    .'<input type="hidden" name="csrf_token" value="'.$e($this->getVar('webinarCsrf')).'">';
if($action==='register'){
 echo '<input type="hidden" name="id" value="'.$e($record['id']).'">';
 echo '<div class="chisimba-form-grid">';
 foreach(['name'=>'text','email'=>'email'] as $field=>$type)echo '<div class="chisimba-form-field"><label for="webinar-'.$field.'">'.$e($r->text($field)).'</label><input id="webinar-'.$field.'" name="'.$field.'" type="'.$type.'" autocomplete="'.$field.'" maxlength="'.($field==='email'?254:200).'" value="'.$e($input[$field]).'" required></div>';
 echo '</div>';
 echo '<div hidden aria-hidden="true"><label for="webinar-website">'.$e($r->text('website')).'</label><input id="webinar-website" name="website" tabindex="-1" autocomplete="off"></div>';
 echo '<div class="chisimba-form-field"><label><input type="checkbox" name="consent" value="1" required '.($input['consent']==='1'?'checked':'').'> '.$e($r->text('consent')).'</label></div><p>'.$e($r->text('registration_help')).'</p>';
}else{
 echo '<input type="hidden" name="token" value="'.$e($this->getVar('webinarToken')).'"><p>'.$e($r->text($action.'_help')).'</p>';
}
echo '<div class="chisimba-form-actions"><button type="submit" class="button chisimba-button-primary">'.$this->getObject('iconservice','ui')->render($action==='unsubscribe'?'mail-x':'check',['decorative'=>true]).'<span>'.$e($r->text($action)).'</span></button></div></form>';
