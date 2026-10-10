<?php
/** Shared semantic template helpers. @author Derek Keats <derek@dkeats.com> */
$esc=static fn($v)=>htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');
$t=fn($key)=>$esc($eventService->text($key));
$url=fn($action,$params=[])=>$esc($eventService->url($action,$params));
$fieldPrefix='';
$eventFieldError=$eventFieldError??[];
$field=static function($key,$value='',$type='text',$required=false,$rows=3,$hint='')use($esc,$t,&$fieldPrefix,$eventFieldError) {
    $id=$fieldPrefix.$key;
    $error=isset($eventFieldError[$key])?' aria-invalid="true" aria-describedby="'.$esc($id).'-error"':'';
    echo '<div class="chisimba-form-field'.($type==='textarea'&&$rows<=3?' chisimba-form-field--brief':'').'"><label for="'.$esc($id).'">'.$t($key==='name'?'booking_name':$key).($required?' <small>('.$t('required').')</small>':'').'</label>';
    if($type==='textarea') echo '<textarea id="'.$esc($id).'" name="'.$esc($key).'" rows="'.(int)$rows.'"'.($required?' required':'').$error.'>'.$esc($value).'</textarea>';
    else echo '<input id="'.$esc($id).'" name="'.$esc($key).'" type="'.$esc($type).'" value="'.$esc($value).'"'.($required?' required':'').$error.'>';
    if($hint!=='') echo '<small class="chisimba-field-help">'.$t($hint).'</small>';
    if(isset($eventFieldError[$key])) echo '<p id="'.$esc($id).'-error" class="chisimba-notice chisimba-notice--error">'.$t($eventFieldError[$key]).'</p>';
    echo '</div>';
};
$hidden=static function($key,$value)use($esc) { echo '<input type="hidden" name="'.$esc($key).'" value="'.$esc($value).'">'; };
$csrf=static function()use($hidden,$eventCsrf) { $hidden('csrf_token',$eventCsrf); };
$select=static function($key,$choices,$value)use($esc,$t) {
    echo '<div class="chisimba-form-field"><label for="'.$esc($key).'">'.$t($key).'</label><select id="'.$esc($key).'" name="'.$esc($key).'">';
    foreach($choices as $choice) echo '<option value="'.$esc($choice).'"'.((string)$value===(string)$choice?' selected':'').'>'.$t($choice===''?'not_specified':$choice).'</option>';
    echo '</select></div>';
};
if(!empty($eventError)) { echo '<div role="alert" class="chisimba-notice chisimba-notice--error"><p>'.$t($eventError).'</p>'; foreach($eventFieldError as $key=>$message) echo '<p><a href="#'.$esc($key).'">'.$t($key).': '.$t($message).'</a></p>'; echo '</div>'; }
if(!empty($eventNotice)) echo '<p role="status" class="chisimba-notice chisimba-notice--success">'.$t($eventNotice).'</p>';
