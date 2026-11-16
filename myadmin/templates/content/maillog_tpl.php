<?php
$r=$this->getObject('mailreportview');$e=['mailreportview','escape'];$d=$this->getVar('mailReport');
echo '<section class="chisimba-form-card chisimba-form-card--wide"><div class="chisimba-form-actions"><h1>'.$e($r->text('delivery_log')).'</h1>'.$this->getObject('contextualhelp','help')->show('myadmin','mail-report',true).'<a class="button" href="'.$e($this->uri(null,'myadmin')).'">'.$e($r->text('health_link')).'</a></div><p>'.$e($r->text('mail_report_help')).'</p><p>'.$e($d['timezone']).'</p>';
echo '<form method="get" action="'.$e($this->uri(null,'myadmin')).'" class="chisimba-form-actions"><input type="hidden" name="module" value="myadmin"><input type="hidden" name="action" value="maillog">';
foreach(['search'=>'search','date'=>'date'] as $key=>$type)echo '<div class="chisimba-form-field"><label for="mail-'.$key.'">'.$e($r->text('mail_'.$key)).'</label><input id="mail-'.$key.'" name="'.$key.'" type="'.$type.'" value="'.$e($d[$key]).'"'.($key==='search'?' maxlength="200"':'').'></div>';
echo '<div class="chisimba-form-field"><label for="mail-status">'.$e($r->text('mail_status')).'</label><select id="mail-status" name="status"><option value="">'.$e($r->text('mail_all')).'</option>';
foreach(['queued','sending','sent','failed','cancelled'] as $status)echo '<option value="'.$status.'"'.($d['status']===$status?' selected':'').'>'.$e($r->status($status)).'</option>';
echo '</select></div><button type="submit" class="button">'.$e($r->text('mail_filter')).'</button></form><p>'.$e(sprintf($r->text('mail_page'),$d['page'],$d['pages'],$d['total'])).'</p>';
if(!$d['rows'])echo '<p>'.$e($r->text('mail_empty')).'</p>';
else {
 echo '<div class="chisimba-table-scroll"><table><thead><tr>';
 foreach(['recipient','subject','status','created','sent_at','attempts','transport','response'] as $key)echo '<th scope="col">'.$e($r->text('mail_'.$key)).'</th>';
 echo '</tr></thead><tbody>';
 foreach($d['rows'] as $row){echo '<tr>';foreach(['recipient','subject','status','date_created','sent_at','attempts','transport','response_code'] as $key)echo '<td class="chisimba-wrap-anywhere">'.$e($key==='status'?$r->status($row[$key]):($row[$key]??'—')).'</td>';echo '</tr>';}
 echo '</tbody></table></div>';
}
echo '<nav class="chisimba-form-actions">';
foreach(['previous'=>-1,'next'=>1] as $label=>$delta){$page=$d['page']+$delta;if($page>=1&&$page<=$d['pages'])echo '<a class="button" href="'.$e($this->uri(['action'=>'maillog','date'=>$d['date'],'search'=>$d['search'],'status'=>$d['status'],'page'=>$page],'myadmin')).'">'.$e($r->text('mail_'.$label)).'</a>';}
echo '</nav><p>'.$e($r->text('mail_privacy')).'</p></section>';
