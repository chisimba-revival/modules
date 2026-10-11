<?php
/** Administrator controls and aggregate progress for automated webinar mail. @author Derek Keats */
if(empty($GLOBALS['kewl_entry_point_run']))die('No direct access');
class webinarreminders extends dbTable
{
 public function init($tableName=null,$pearDb=null,$errorCallback='globalPearErrorHandler'){
  parent::init('tbl_webinar_records',$pearDb,$errorCallback);$this->loadClass('webinarschedule','webinar');
 }
 public function allowed(){$u=$this->getObject('user','security');return $u->isLoggedIn()&&$u->isAdmin();}
 public function settings(array $record){
  $p=json_decode($record['payload'],true);$saved=$p['reminders']??[];
  $config=$this->getObject('dbsysconfig','sysconfig');
  return $saved+['subscribers'=>$config->getValue('WEBINAR_DAY_ANNOUNCEMENTS_ENABLED','webinar','FALSE')==='TRUE',
   'subscribers_since'=>(int)$config->getValue('WEBINAR_DAY_ANNOUNCEMENTS_SINCE','webinar',0),
   'morning'=>true,'ninety'=>true,'monday'=>true,'morning_time'=>'08:00','lead_minutes'=>90];
 }
 /** Morning sends deliberately follow SAST, independently of the event's time zone. */
 public function due(array $record,$kind){
  $settings=$this->settings($record);if(empty($settings[$kind]))return null;
  $start=webinarschedule::start($record);if(!$start)return null;
  if($kind==='monday')return webinarschedule::reminderDue($record,'monday');
  if($kind==='ninety')return $start->getTimestamp()-60*(int)$settings['lead_minutes'];
  if(!in_array($kind,['subscribers','morning'],true))return null;
  [$hour,$minute]=array_map('intval',explode(':',$settings['morning_time']));
  $due=$start->setTimezone(new DateTimeZone('Africa/Johannesburg'))->setTime($hour,$minute)->getTimestamp();
  return $due<$start->getTimestamp()?$due:null;
 }
 public function eligibleNow(array $record,$kind,$now){
  $due=$this->due($record,$kind);$settings=$this->settings($record);
  return webinarschedule::canRegister($record,$now)&&$due!==null&&$due<=$now&&$now-$due<3600
   &&$due>=(int)($settings[$kind.'_since']??0);
 }
 public function subscriberKey(array $record){return 'webinar-day:'.$record['id'].':'.webinarschedule::start($record)->getTimestamp();}
 public function save($id,$version,array $input){
  if(!$this->allowed())throw new DomainException('editor_forbidden');
  $time=$input['morning_time']??'';$lead=$input['lead_minutes']??'';
  if(!is_string($time)||!preg_match('/^(?:[01][0-9]|2[0-3]):[0-5][0-9]$/D',$time))throw new DomainException('reminder_invalid');
  $lead=filter_var($lead,FILTER_VALIDATE_INT,['options'=>['min_range'=>15,'max_range'=>1440]]);if($lead===false)throw new DomainException('reminder_invalid');
  $store=$this->getObject('webinareditstore','webinar');$store->begin();
  try{
   $record=$store->record($id,true);if(!$record||$record['kind']!=='webinar')throw new DomainException('editor_invalid');
   $this->loadClass('webinareditservice','webinar');
   if(!hash_equals(webinareditservice::version($record),(string)$version))throw new DomainException('editor_conflict');
   $p=json_decode($record['payload'],true,512,JSON_THROW_ON_ERROR);$old=$this->settings($record);$new=$old;
   foreach(['subscribers','morning','ninety','monday'] as $kind){$new[$kind]=($input[$kind]??'')==='1';if($new[$kind]&&!$old[$kind])$new[$kind.'_since']=time();}
   $new['morning_time']=$time;$new['lead_minutes']=$lead;$p['reminders']=$new;$p['editorial_revision']=bin2hex(random_bytes(16));
   $record['payload']=json_encode($p,JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
   $record['source_hash']=hash('sha256',$record['title'].'|'.$record['presented_at'].'|'.$record['payload']);
   $store->persist($record,true);$store->commit();return $record;
  }catch(Throwable $e){$store->rollback();throw $e;}
 }
 /** No recipient identities are returned to the dashboard. */
 public function progress(array $record,$now=null){
  if(!$this->allowed())throw new DomainException('editor_forbidden');$now=$now??time();$start=webinarschedule::start($record);if(!$start)return [];
  $rows=[];$settings=$this->settings($record);
  foreach(['subscribers','morning','ninety','monday'] as $kind){
   $due=$this->due($record,$kind);$counts=['queued'=>0,'sent'=>0,'failed'=>0,'cancelled'=>0];$total=0;$cursor=0;
   if($kind==='subscribers'){
    $campaign=substr(hash('sha256','audience:'.$this->subscriberKey($record)),0,32);
    $saved=$this->selectPrepared('SELECT payload FROM tbl_audience_campaigns WHERE id=?',[$campaign]);
    $cursor=$saved?(int)(json_decode($saved[0]['payload'],true)['cursor']??0):0;
    $total=$saved?count(json_decode($saved[0]['payload'],true)['recipients']??[]):(int)$this->selectPrepared("SELECT COUNT(*) n FROM tbl_audience_contacts WHERE state='subscribed'")[0]['n'];
    $stats=$this->selectPrepared('SELECT status,COUNT(*) n FROM tbl_communications_outbox WHERE idempotency_key LIKE ? GROUP BY status',['audience:'.$campaign.':%']);
   }else{
    $stats=$this->selectPrepared("SELECT o.status,COUNT(*) n FROM tbl_communications_outbox o
     INNER JOIN tbl_webinar_registrations r ON r.id=JSON_UNQUOTE(JSON_EXTRACT(o.metadata_json,'$.registration_id'))
     WHERE r.webinar_id=? AND JSON_UNQUOTE(JSON_EXTRACT(o.metadata_json,'$.policy_module'))='webinar'
     AND JSON_UNQUOTE(JSON_EXTRACT(o.metadata_json,'$.kind'))=?
     AND JSON_UNQUOTE(JSON_EXTRACT(o.metadata_json,'$.starts_at'))=? GROUP BY o.status",[$record['id'],$kind,(string)$start->getTimestamp()]);
    $total=(int)$this->selectPrepared("SELECT COUNT(DISTINCT r.contact_id) n FROM tbl_webinar_registrations r
     INNER JOIN tbl_audience_contacts c ON c.id=r.contact_id WHERE r.webinar_id=? AND r.state='confirmed'
     AND c.state='subscribed' AND c.revision=r.contact_revision AND r.confirmed_at<=?",[$record['id'],gmdate('Y-m-d H:i:s',$due??$now)])[0]['n'];
   }
   foreach($stats as $stat)if(isset($counts[$stat['status']]))$counts[$stat['status']]=(int)$stat['n'];
   $skipped=max(0,$cursor-array_sum($counts));$counts['cancelled']+=$skipped;
   $pending=max(0,$total-array_sum($counts));
   $state=empty($settings[$kind])?'reminder_disabled':($due===null?'reminder_no_time':
    (!webinarschedule::canRegister($record,$now)?'reminder_closed':($now<$due?'reminder_scheduled':
     ($counts['queued']?'reminder_queued':($pending&&$now-$due>=3600?'reminder_missed':($pending?'reminder_pending':'reminder_complete'))))));
   $rows[$kind]=['due'=>$due,'state'=>$state,'pending'=>$pending]+$counts;
  }return $rows;
 }
 public function workerState(){
  if(!$this->allowed())throw new DomainException('editor_forbidden');
  return $this->selectPrepared('SELECT last_run_at,failed_count FROM tbl_communications_worker_state WHERE id=?',['mail'])[0]??null;
 }
}
