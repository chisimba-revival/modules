<?php
$apply=in_array('--apply',$argv??[],true);$data=json_decode(file_get_contents('/tmp/ltb-draft-20260919.json'),true,512,JSON_THROW_ON_ERROR);
if(count($data['contacts'])!==5||count($data['events'])!==1||$data['events'][0]['ID']!='41397'||$data['events'][0]['post_status']!=='draft')throw new RuntimeException('Unexpected input');
chdir('/var/www/html');$_SERVER['REQUEST_METHOD']='CLI';$_SERVER['HTTP_HOST']='migrate.learnthebirds.com';$_SERVER['SCRIPT_NAME']='/index.php';$_SERVER['PHP_SELF']='/index.php';$_SERVER['QUERY_STRING']='';$GLOBALS['kewl_entry_point_run']=true;require 'classes/core/engine_class_inc.php';$e=new engine();
if(parse_url($e->getObject('altconfig','config')->getSiteRoot(),PHP_URL_HOST)!=='migrate.learnthebirds.com'||$e->getObject('dbsysconfig','sysconfig')->getValue('COMMUNICATION_TRANSPORT','communications')!=='null')throw new RuntimeException('Wrong environment');
$db=$e->getDbObj();$rows=function($sql)use($db){$r=$db->query($sql);if(PEAR::isError($r))throw new RuntimeException('Read failed');return $r->fetchAll(MDB2_FETCHMODE_ASSOC);};$exec=function($sql)use($db){$r=$db->exec($sql);if(PEAR::isError($r))throw new RuntimeException('Write failed');};$q=fn($v)=>$v===''?"''":$db->quote((string)$v);$insert=function($t,$r)use($exec,$q){$exec('INSERT INTO '.$t.' ('.implode(',',array_keys($r)).') VALUES ('.implode(',',array_map($q,array_values($r))).')');};
$counts=function()use($rows){$a=[];foreach(['tbl_webinar_records','tbl_webinar_registrations','tbl_audience_consent','tbl_audience_contacts','tbl_communications_outbox'] as $t)$a[$t]=hash('sha256',json_encode($rows('SELECT * FROM '.$t.' ORDER BY id')));return $a;};$before=$counts();$exec('START TRANSACTION');
try{
 $source=$data['events'][0];$key='learnthebirds.com|webinar|41397';$id=substr(hash('sha256',$key),0,32);$old=$rows('SELECT * FROM tbl_webinar_records WHERE source_key='.$q($key).' FOR UPDATE');
 if(!$old){
  $speakers=[];$ids=@unserialize($source['dates']['_event_organizer_ids']??'', ['allowed_classes'=>false]);foreach(is_array($ids)?$ids:[] as $sid){if(!ctype_digit((string)$sid))throw new RuntimeException('Bad speaker reference');$s=$rows('SELECT id FROM tbl_webinar_records WHERE source_key='.$q('learnthebirds.com|speaker|'.$sid));if($s)$speakers[]=$s[0]['id'];}
  $payload=['description'=>$e->getObject('richtextsanitizer','utilities')->cleanHtml($source['post_content']),'timezone'=>$source['dates']['_event_timezone'],'ends_at'=>$source['dates']['_event_end_date'],'registration_open'=>false,'speakers'=>$speakers,'source_slug'=>$source['post_name'],'image'=>'','recording'=>'','migration_source'=>$source,'reschedule_pending'=>true];
  $encoded=json_encode($payload,JSON_THROW_ON_ERROR|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);$date=$source['dates']['_event_start_date'];
  $e->getObject('webinareditstore','webinar')->persist(['id'=>$id,'kind'=>'webinar','source_key'=>$key,'source_hash'=>hash('sha256',$source['post_title'].'|'.$date.'|'.$encoded),'title'=>$source['post_title'],'status'=>'draft','presented_at'=>$date,'payload'=>$encoded],null);
 }elseif($old[0]['status']!=='draft')throw new RuntimeException('Existing webinar no longer draft');
 $new=0;$kept=0;$byId=array_column($data['contacts'],null,'id');
 foreach($data['memberships'] as $m){if((int)$m['list_id']!==15||$m['status']!=='subscribed')continue;$c=$byId[$m['contact_id']];$contacts=$rows('SELECT * FROM tbl_audience_contacts WHERE email='.$q(strtolower(trim($c['email']))).' FOR UPDATE');if(count($contacts)>1)throw new RuntimeException('Duplicate contact');
  if(!$contacts){
   $email=strtolower(trim($c['email']));if(!filter_var($email,FILTER_VALIDATE_EMAIL))throw new RuntimeException('Invalid contact');
   $cid=substr(hash('sha256','icegram:learnthebirds.com:Users:'.$c['id']),0,32);
   $contact=['id'=>$cid,'email'=>$email,'name'=>mb_substr(trim($c['first_name'].' '.$c['last_name']),0,200),'state'=>'unsubscribed','revision'=>0,'created_at'=>gmdate('Y-m-d H:i:s')];
   $insert('tbl_audience_contacts',$contact);
   $insert('tbl_audience_consent',['id'=>substr(hash('sha256','preserved-suppression|'.$cid),0,32),'contact_id'=>$cid,'action'=>'import_suppressed','source'=>'icegram:learnthebirds.com:list:15:'.$c['id'],'recorded_at'=>gmdate('Y-m-d H:i:s'),'wording'=>'Preserved draft registration without granting email eligibility. Excluded from general subscriber migration; remains suppressed.']);
  }else $contact=$contacts[0];
  $existing=$rows('SELECT * FROM tbl_webinar_registrations WHERE webinar_id='.$q($id).' AND contact_id='.$q($contact['id']));if($existing){$kept++;continue;}
  $rid=substr(hash('sha256','icegram:registration:15:'.$c['id']),0,32);
  $insert('tbl_webinar_registrations',['id'=>$rid,'webinar_id'=>$id,'contact_id'=>$contact['id'],'state'=>'confirmed','confirm_hash'=>hash('sha256',random_bytes(32)),'expires_at'=>0,'contact_revision'=>$contact['revision'],'created_at'=>gmdate('Y-m-d H:i:s'),'confirmed_at'=>gmdate('Y-m-d H:i:s')]);
  $insert('tbl_audience_consent',['id'=>substr(hash('sha256','registration-provenance|'.$rid),0,32),'contact_id'=>$contact['id'],'action'=>'import_registration','source'=>'icegram:learnthebirds.com:list:15:'.$c['id'],'recorded_at'=>gmdate('Y-m-d H:i:s'),'wording'=>'Preserved existing registration for private draft, pending rescheduling. Source subscribed_at: '.$m['subscribed_at'].'. No mail or subscription changes.']);$new++;
 }
 if($new+$kept!==5)throw new RuntimeException('Registration count mismatch');
 if($e->getObject('webinarstore','webinar')->one($id)!==null)throw new RuntimeException('Draft publicly accessible');
 $after=$counts();foreach(['tbl_communications_outbox'] as $t)if($before[$t]!==$after[$t])throw new RuntimeException('Unexpected side effects');
 $exec($apply?'COMMIT':'ROLLBACK');if(!$apply&&$counts()!==$before)throw new RuntimeException('Rollback mismatch');
 echo json_encode(['applied'=>$apply,'new_registrations'=>$new,'existing'=>$kept,'private_draft'=>true,'date_pending'=>true,'mail_created'=>0,'source_date_retained'=>$source['dates']['_event_start_date'],'source_timezone'=>$source['dates']['_event_timezone']])."\n";
}catch(Throwable $ex){$exec('ROLLBACK');throw $ex;}
