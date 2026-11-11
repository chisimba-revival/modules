<?php
$r=$this->getObject('webinarrenderer','webinar');$e=['webinarrenderer','escape'];
$data=$this->getVar('webinarAttendees');$record=$data['record'];
$clock=$this->getObject('timeanddateservice','timeanddate-service');
echo '<section class="chisimba-form-card chisimba-form-card--wide"><header class="chisimba-form-actions"><h1>'.$e($r->text('attendees')).'</h1>'.$this->getObject('contextualhelp','help')->show('webinar','attendees',true).'</header>';
echo '<h2>'.$e(html_entity_decode($record['title'],ENT_QUOTES|ENT_HTML5,'UTF-8')).'</h2><p>'.$e(sprintf($r->text('attendees_summary'),$data['total'])).'</p>'.$r->button('manage','arrow-left',['action'=>'manage']);
if(!$data['rows'])echo '<p>'.$e($r->text('attendees_empty')).'</p>';
else {
 echo '<div class="chisimba-table-scroll"><table><thead><tr>';
 foreach(['name','email','confirmed','review'] as $key)echo '<th scope="col">'.$e($r->text('attendees_'.$key)).'</th>';
 echo '</tr></thead><tbody>';
 foreach($data['rows'] as $row){
  echo '<tr><td class="chisimba-wrap-anywhere">'.$e($row['name']).'</td><td class="chisimba-wrap-anywhere">'.$e($row['email']).'</td><td>'.$e($row['confirmed_at']?$clock->formatDateTime($row['confirmed_at']):'—').'</td><td>';
  if($row['review'])echo '<details class="chisimba-review-flag"><summary>'.$this->getObject('iconservice','ui')->render('triangle-alert').' '.$e($r->text('attendees_review')).'</summary><p>'.$e($r->text($row['review'])).'</p></details>';
  echo '</td></tr>';
 }
 echo '</tbody></table></div>';
}
echo '<nav class="chisimba-form-actions">';
if($data['page']>1)echo $r->button('previous','chevron-left',['action'=>'attendees','id'=>$record['id'],'page'=>$data['page']-1]);
if($data['page']<$data['pages'])echo $r->button('next','chevron-right',['action'=>'attendees','id'=>$record['id'],'page'=>$data['page']+1]);
echo '</nav></section>';
