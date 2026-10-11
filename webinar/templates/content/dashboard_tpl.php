<?php
/** Private overview; read-only progress and explicit CSRF-protected controls. */
$r=$this->getObject('webinarrenderer','webinar');$e=['webinarrenderer','escape'];$service=$this->getObject('webinarreminders','webinar');$record=$this->getVar('webinarRecord');
echo '<section class="chisimba-form-card chisimba-form-card--wide"><header class="chisimba-form-actions"><h1>'.$e($r->text('dashboard')).'</h1>'.$this->getObject('contextualhelp','help')->show('webinar','reminders',true).'</header><nav class="chisimba-form-actions">'.$r->button('manage','list',['action'=>'manage']).'<a class="button" href="'.$e($this->uri(['action'=>'campaigns'],'audience')).'">'.$e($r->text('newsletters')).'</a>'.'</nav>';
echo '<p>'.$e($r->text('reminder_help_summary')).'</p><form method="get" class="chisimba-form-actions"><input type="hidden" name="module" value="webinar"><input type="hidden" name="action" value="dashboard"><label for="webinar-dashboard-id">'.$e($r->text('reminder_select')).'</label><select id="webinar-dashboard-id" name="id">';
foreach($this->getVar('webinarDashboardRows') as $row)echo '<option value="'.$e($row['id']).'"'.(($record['id']??'')===$row['id']?' selected':'').'>'.$e(html_entity_decode($row['title'],ENT_QUOTES|ENT_HTML5,'UTF-8')).'</option>';
echo '</select><button type="submit" class="button">'.$e($r->text('reminder_show')).'</button></form>';
$worker=$service->workerState();
echo '<p>'.$e($worker?sprintf($r->text('reminder_worker'),(new DateTimeImmutable($worker['last_run_at']))->setTimezone(new DateTimeZone('Africa/Johannesburg'))->format('Y-m-d H:i:s')):$r->text('reminder_worker_missing')).'</p>';
if($worker&&strtotime($worker['last_run_at'])<time()-300)echo '<p role="status">'.$e($r->text('reminder_worker_stale')).'</p>';
$error=$this->getVar('webinarError');if($error)echo '<p role="alert">'.$e($r->text($error)).'</p>';
if($this->getParam('saved')==='1')echo '<p role="status">'.$e($r->text('reminder_saved')).'</p>';
if($record){
 $counts=$this->getObject('webinarregistrations','webinar')->countsForManagement([$record]);$settings=$service->settings($record);$input=$this->getVar('webinarReminderInput');
 if($input!==null){foreach(['subscribers','morning','ninety','monday'] as $kind)$settings[$kind]=($input[$kind]??'')==='1';$settings['morning_time']=$input['morning_time'];$settings['lead_minutes']=$input['lead_minutes'];}
 echo '<h2>'.$e(html_entity_decode($record['title'],ENT_QUOTES|ENT_HTML5,'UTF-8')).'</h2>'.$r->date($record).'<p class="chisimba-pill">'.$e(sprintf($r->text('management_count'),$counts[$record['id']]??0,webinarschedule::registrationLimit($record))).'</p><p>'.$e($r->text('management_count_help')).'</p><nav class="chisimba-form-actions">'.$r->button('edit','pencil',['action'=>'edit','id'=>$record['id']]).'</nav>';
 echo '<form method="post" class="chisimba-form" action="'.$e($this->uri(['action'=>'save_reminders'],'webinar')).'">';
 foreach(['id'=>$record['id'],'version'=>$input['version']??webinareditservice::version($record),'csrf_token'=>$this->getVar('webinarCsrf')] as $key=>$value)echo '<input type="hidden" name="'.$key.'" value="'.$e($value).'">';
 echo '<div class="chisimba-choice-list">';foreach(['subscribers','morning','ninety','monday'] as $kind)echo '<label><input type="checkbox" name="'.$kind.'" value="1"'.(!empty($settings[$kind])?' checked':'').'> '.$e($r->text('reminder_'.$kind)).'</label>';echo '</div>';
 foreach(['morning_time'=>'time','lead_minutes'=>'number'] as $name=>$type)echo '<div class="chisimba-form-field"><label for="reminder-'.$name.'">'.$e($r->text('reminder_'.$name)).'</label><input id="reminder-'.$name.'" name="'.$name.'" type="'.$type.'" required'.($type==='number'?' min="15" max="1440" step="1"':'').' value="'.$e($settings[$name]).'"></div>';
 echo '<div class="chisimba-form-actions"><button type="submit" class="button">'.$e($r->text('reminder_save')).'</button></div></form>';
 echo '<div class="chisimba-table-wrap" role="region" tabindex="0" aria-label="'.$e($r->text('reminder_progress')).'"><table class="chisimba-table"><caption>'.$e($r->text('reminder_progress')).'</caption><thead><tr><th scope="col">'.$e($r->text('reminder_type')).'</th><th scope="col">'.$e($r->text('reminder_schedule')).'</th>';
 foreach(['pending','queued','sent','failed','cancelled'] as $key)echo '<th scope="col">'.$e($r->text('reminder_'.$key)).'</th>';echo '</tr></thead><tbody>';
 foreach($service->progress($record) as $kind=>$status){
  echo '<tr><th scope="row">'.$e($r->text('reminder_'.$kind)).'<br><small>'.$e($r->text($status['state'])).'</small></th><td>'.$e($status['due']===null?'—':(new DateTimeImmutable('@'.$status['due']))->setTimezone(new DateTimeZone('Africa/Johannesburg'))->format('j M Y H:i')).'</td>';
  foreach(['pending','queued','sent','failed','cancelled'] as $key)echo '<td>'.(int)$status[$key].'</td>';echo '</tr>';
 }
 echo '</tbody></table></div>';
 echo '<p>'.$e($r->text('reminder_help_counts')).'</p><p>'.$e($r->text('reminder_help_delivery')).'</p>';
}
echo '</section>';
