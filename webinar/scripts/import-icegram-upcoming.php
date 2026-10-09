<?php
/** One-off, staging-only Icegram migration. No account creation or outbound mail. */
if(PHP_SAPI!=='cli')exit(1);
$mode=$argv[1]??'--dry-run';if(!in_array($mode,['--schema','--dry-run','--rehearse','--apply'],true))throw new RuntimeException('Invalid mode');
$input=json_decode(file_get_contents('/tmp/ltb-audience-20260919.json'),true,512,JSON_THROW_ON_ERROR);
if(($input['source']??'')!=='learnthebirds.com'||time()-strtotime($input['exported_at'])>86400)throw new RuntimeException('Refresh source');
$GLOBALS['kewl_entry_point_run']=true;require '/tmp/ltb-icegram-policy.php';
$users=array_values(array_filter($input['memberships'],fn($m)=>(int)$m['list_id']===5));
$plan=icegramimportplan::build($input['contacts'],$users,$input['blocked']);$eligible=[];foreach($plan['eligible'] as $c)$eligible[$c['source_id']]=$c;
chdir('/var/www/html');$_SERVER['REQUEST_METHOD']='CLI';$_SERVER['HTTP_HOST']='migrate.learnthebirds.com';$_SERVER['SCRIPT_NAME']='/index.php';$_SERVER['PHP_SELF']='/index.php';$_SERVER['QUERY_STRING']='';require 'classes/core/engine_class_inc.php';$e=new engine();
if(parse_url($e->getObject('altconfig','config')->getSiteRoot(),PHP_URL_HOST)!=='migrate.learnthebirds.com'||$e->getObject('dbsysconfig','sysconfig')->getValue('COMMUNICATION_TRANSPORT','communications')!=='null')throw new RuntimeException('Protected staging with mail off required');
$db=$e->getDbObj();$rows=function($sql)use($db){$r=$db->query($sql);if(PEAR::isError($r))throw new RuntimeException('Read failed');return $r->fetchAll(MDB2_FETCHMODE_ASSOC);};$exec=function($sql)use($db){$r=$db->exec($sql);if(PEAR::isError($r))throw new RuntimeException('Write failed');return $r;};$q=fn($v)=>$v===''?"''":$db->quote((string)$v);
$insert=function($table,$data)use($exec,$q){$exec('INSERT INTO '.$table.' ('.implode(',',array_keys($data)).') VALUES ('.implode(',',array_map($q,array_values($data))).')');};
$required=['tbl_audience_contacts'=>[['id'],['email']],'tbl_audience_consent'=>[['id']],'tbl_webinar_registrations'=>[['id'],['webinar_id','contact_id'],['confirm_hash']]];
foreach($required as $table=>$groups){
 $status=$rows("SHOW TABLE STATUS WHERE Name='$table'");if(($status[0]['engine']??'')!=='InnoDB')throw new RuntimeException('InnoDB required');
 $indexes=[];foreach($rows('SHOW INDEX FROM '.$table) as $ix)if(!$ix['non_unique'])$indexes[$ix['key_name']][]=$ix['column_name'];
 foreach($groups as $cols)if(!in_array($cols,$indexes,true)){
  if($mode!=='--schema')throw new RuntimeException('Missing uniqueness; run --schema first');
  $names=implode(',',$cols);if($rows("SELECT COUNT(*) n FROM $table GROUP BY $names HAVING COUNT(*)>1"))throw new RuntimeException('Duplicates require review');
  $exec('ALTER TABLE '.$table.' ADD UNIQUE KEY migration_'.implode('_',$cols).' ('.$names.')');
 }
}
if($mode==='--schema'){echo "Unique constraints verified.\n";exit;}
$summary=$plan['summary']+['mode'=>$mode,'new_contacts'=>0,'existing_contacts'=>0,'preserved_suppression'=>0,'registrations'=>[],'date_changes'=>0];
$hashes=function()use($rows){$out=[];foreach(['tbl_audience_contacts','tbl_audience_consent','tbl_webinar_registrations','tbl_communications_outbox','tbl_webinar_records'] as $table)$out[$table]=hash('sha256',json_encode($rows('SELECT * FROM '.$table.' ORDER BY id')));return $out;};$before=$hashes();
$exec('START TRANSACTION');
try{
 $contactIds=[];
 // Refuse a repeat import that would silently retain someone newly unsubscribed at source.
 foreach($rows("SELECT source FROM tbl_audience_consent WHERE action='import_subscribed' AND source LIKE 'icegram:learnthebirds.com:Users:%'") as $old){$sid=substr($old['source'],strlen('icegram:learnthebirds.com:Users:'));if(!isset($eligible[$sid]))throw new RuntimeException('Changed source consent requires reconciliation');}
 foreach($eligible as $sid=>$c){
  $existing=$rows('SELECT * FROM tbl_audience_contacts WHERE email='.$q($c['email']).' FOR UPDATE');
  if($existing){$summary['existing_contacts']++;if($existing[0]['state']!=='subscribed'){$summary['preserved_suppression']++;continue;}$contactIds[$sid]=$existing[0];continue;}
  $id=substr(hash('sha256','icegram:learnthebirds.com:Users:'.$sid),0,32);
  $record=['id'=>$id,'email'=>$c['email'],'name'=>$c['name'],'state'=>'subscribed','revision'=>0,'created_at'=>gmdate('Y-m-d H:i:s')];
  $insert('tbl_audience_contacts',$record);
  $insert('tbl_audience_consent',['id'=>substr(hash('sha256','consent|'.$id),0,32),'contact_id'=>$id,'action'=>'import_subscribed','source'=>'icegram:learnthebirds.com:Users:'.$sid,'recorded_at'=>gmdate('Y-m-d H:i:s'),'wording'=>'Existing Icegram Users-list subscription. Source subscribed_at: '.$c['source_subscribed_at'].'; optin_type: '.$c['source_optin_type'].'. Imported without email or account creation.']);
  $contactIds[$sid]=$record;$summary['new_contacts']++;
 }
 $store=$e->getObject('webinareditstore','webinar');
 foreach($input['events'] as $event){
  $matches=$rows('SELECT * FROM tbl_webinar_records WHERE source_key='.$q('learnthebirds.com|webinar|'.$event['ID']).' FOR UPDATE');if(count($matches)!==1)throw new RuntimeException('Event mapping missing');$record=$matches[0];$payload=json_decode($record['payload'],true,512,JSON_THROW_ON_ERROR);
  $date=$event['dates']['_event_start_date'];$end=$event['dates']['_event_end_date'];$zone=$event['dates']['_event_timezone'];
  if($zone!=='Africa/Johannesburg'||!preg_match('/^202[67]-\d{2}-\d{2} 19:00:00$/D',$date)||substr($date,0,10)!==substr($end,0,10)||substr($end,11)!=='20:00:00')throw new RuntimeException('Unexpected schedule');
  if($record['presented_at']!==$date||($payload['ends_at']??'')!==$end){
   if($event['ID']!='42607'||$date!=='2027-01-21 19:00:00'||!in_array($record['presented_at'],['2026-09-17 19:00:00','2027-01-21 19:00:00'],true))throw new RuntimeException('Unreviewed date change');
   $payload['original_source_hash']=$payload['original_source_hash']??$record['source_hash'];$payload['ends_at']=$end;$payload['timezone']=$zone;
   $record['presented_at']=$date;$record['payload']=json_encode($payload,JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);$record['source_hash']=hash('sha256',$record['title'].'|'.$date.'|'.$record['payload']);$store->persist($record,$matches[0]);$summary['date_changes']++;
  }
  $n=['subscribed_entries'=>0,'new'=>0,'existing'=>0,'excluded'=>0];$seen=[];
  foreach($input['memberships'] as $m){if((int)$m['list_id']!==(int)$event['list_id']||$m['status']!=='subscribed')continue;$n['subscribed_entries']++;
   $sid=$m['contact_id'];if(isset($seen[$sid]))throw new RuntimeException('Duplicate event subscription');$seen[$sid]=true;
   if(!isset($contactIds[$sid])){$n['excluded']++;continue;}$contact=$contactIds[$sid];
   $existing=$rows('SELECT * FROM tbl_webinar_registrations WHERE webinar_id='.$q($record['id']).' AND contact_id='.$q($contact['id']).' FOR UPDATE');
   if($existing){$n['existing']++;continue;}
   $id=substr(hash('sha256','icegram:registration:'.$event['list_id'].':'.$sid),0,32);
   $insert('tbl_webinar_registrations',['id'=>$id,'webinar_id'=>$record['id'],'contact_id'=>$contact['id'],'state'=>'confirmed','confirm_hash'=>hash('sha256',random_bytes(32)),'expires_at'=>0,'contact_revision'=>$contact['revision'],'created_at'=>gmdate('Y-m-d H:i:s'),'confirmed_at'=>gmdate('Y-m-d H:i:s')]);
   $insert('tbl_audience_consent',['id'=>substr(hash('sha256','registration-provenance|'.$id),0,32),'contact_id'=>$contact['id'],'action'=>'import_registration','source'=>'icegram:learnthebirds.com:list:'.$event['list_id'].':'.$sid,'recorded_at'=>gmdate('Y-m-d H:i:s'),'wording'=>'Existing webinar list registration; source subscribed_at: '.$m['subscribed_at'].'. No new subscription or email.']);$n['new']++;
  }
  $summary['registrations'][$event['ID']]=$n;
 }
 $after=$hashes();if($after['tbl_communications_outbox']!==$before['tbl_communications_outbox'])throw new RuntimeException('Unexpected mail mutation');
 if($mode==='--apply')$exec('COMMIT');else{$exec('ROLLBACK');if($hashes()!==$before)throw new RuntimeException('Rollback mismatch');}
 $summary['mail_created']=0;$summary['rollback_verified']=$mode!=='--apply';echo json_encode($summary,JSON_PRETTY_PRINT)."\n";
}catch(Throwable $ex){$exec('ROLLBACK');throw $ex;}
