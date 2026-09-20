<?php
/** Staging-only subscriber import/rehearsal. No registration, account or mail creation.
 * @author Derek Keats
 */
if(PHP_SAPI!=='cli')exit(1);
$path=$argv[1]??'';$mode=$argv[2]??'--dry-run';
if(!in_array($mode,['--dry-run','--rehearse','--apply'],true)||!is_file($path))throw new RuntimeException('Expected reviewed export and mode');
$input=json_decode(file_get_contents($path),true,512,JSON_THROW_ON_ERROR);
if(($input['version']??null)!==1||($input['source']??'')!=='https://learnthebirds.com'||($input['list_name']??'')!=='Users'||($input['list_id']??null)!==5)throw new RuntimeException('Unexpected source');
$time=strtotime($input['exported_at']??'');if(!$time||$time>time()+300||($mode==='--apply'&&time()-$time>86400))throw new RuntimeException('Refresh source before applying');
$eligible=$input['eligible']??null;$sourceIds=$input['source_ids']??null;
if(!is_array($eligible)||!is_array($sourceIds)||count($eligible)!==($input['summary']['eligible']??-1)||count($sourceIds)!==($input['summary']['source_contacts']??-1)||!$eligible)throw new RuntimeException('Incomplete export');
$sourceSet=[];foreach($sourceIds as $id){if(!is_string($id)||!ctype_digit($id)||isset($sourceSet[$id]))throw new RuntimeException('Invalid source identity');$sourceSet[$id]=true;}
$bySource=[];$emails=[];foreach($eligible as $row){$id=$row['source_id']??'';$email=$row['email']??'';
 if(!is_string($id)||!isset($sourceSet[$id])||isset($bySource[$id])||!is_string($email)||$email!==strtolower(trim($email))||!filter_var($email,FILTER_VALIDATE_EMAIL)||strlen($email)>254||isset($emails[$email])||!is_string($row['name']??null)||mb_strlen($row['name'])>200)throw new RuntimeException('Invalid eligible contact');
 $bySource[$id]=$row;$emails[$email]=true;
}
chdir('/var/www/html');$_SERVER['REQUEST_METHOD']='CLI';$_SERVER['HTTP_HOST']='migrate.learnthebirds.com';$_SERVER['SCRIPT_NAME']='/index.php';$_SERVER['PHP_SELF']='/index.php';$_SERVER['QUERY_STRING']='';$GLOBALS['kewl_entry_point_run']=true;require 'classes/core/engine_class_inc.php';$engine=new engine();
if(parse_url($engine->getObject('altconfig','config')->getSiteRoot(),PHP_URL_HOST)!=='migrate.learnthebirds.com')throw new RuntimeException('Protected migration staging only');
$config=$engine->getObject('dbsysconfig','sysconfig');if($config->getValue('COMMUNICATION_TRANSPORT','communications')!=='null')throw new RuntimeException('Stop sending before import');
$db=$engine->getDbObj();$rows=function($sql)use($db){$r=$db->query($sql);if(PEAR::isError($r))throw new RuntimeException('Import read failed');return $r->fetchAll(MDB2_FETCHMODE_ASSOC);};$exec=function($sql)use($db){$r=$db->exec($sql);if(PEAR::isError($r))throw new RuntimeException('Import write failed');};$q=fn($value)=>$value===''?"''":$db->quote($value);
foreach(['tbl_audience_contacts','tbl_audience_consent'] as $table){$status=$rows("SHOW TABLE STATUS WHERE Name='".$table."'");if(($status[0]['engine']??'')!=='InnoDB')throw new RuntimeException('Transactional audience schema required');}
$indexes=$rows('SHOW INDEX FROM tbl_audience_contacts');$unique=[];foreach($indexes as $index)if(!$index['non_unique'])$unique[$index['key_name']][]=$index['column_name'];
if(!in_array(['id'],$unique,true)||!in_array(['email'],$unique,true))throw new RuntimeException('Unique audience ID/email indexes required');
$counts=function()use($rows){$r=[];foreach(['tbl_audience_contacts','tbl_audience_consent','tbl_webinar_registrations','tbl_communications_outbox'] as $table)$r[$table]=(int)$rows('SELECT COUNT(*) AS n FROM '.$table)[0]['n'];return $r;};$before=$counts();$summary=['mode'=>$mode,'eligible'=>count($eligible),'new'=>0,'preserved_existing'=>0,'preserved_unsubscribed'=>0];
$prefix='icegram:learnthebirds.com:Users:';$exec('START TRANSACTION');
try{
 // Repeated imports never quietly retain a source subscriber who has since opted out.
 foreach($rows("SELECT contact_id,source FROM tbl_audience_consent WHERE action='import_subscribed' AND source LIKE 'icegram:learnthebirds.com:Users:%'") as $prior){$sourceId=substr($prior['source'],strlen($prefix));if(!isset($bySource[$sourceId]))throw new RuntimeException('A previously imported subscription changed; reconcile before retrying');}
 foreach($bySource as $sourceId=>$row){
  $existing=$rows('SELECT id,email,state FROM tbl_audience_contacts WHERE email='.$q($row['email']).' FOR UPDATE');
  if($existing){$summary['preserved_existing']++;if($existing[0]['state']==='unsubscribed')$summary['preserved_unsubscribed']++;continue;}
  $id=substr(hash('sha256',$prefix.$sourceId),0,32);if($rows('SELECT id FROM tbl_audience_contacts WHERE id='.$q($id).' FOR UPDATE'))throw new RuntimeException('Imported identity conflict');$summary['new']++;
  if($mode==='--dry-run')continue;
  $exec('INSERT INTO tbl_audience_contacts (id,email,name,state,revision,created_at) VALUES ('.implode(',',[$q($id),$q($row['email']),$q($row['name']),$q('subscribed'),'0',$q(gmdate('Y-m-d H:i:s'))]).')');
  $wording='Imported an existing Users-list subscription; no new consent or account was created. Source subscribed_at: '.(string)($row['source_subscribed_at']??'').'; source optin_type: '.(string)($row['source_optin_type']??'').'.';
  if(mb_strlen($wording)>1000)throw new RuntimeException('Invalid source provenance');
  $exec('INSERT INTO tbl_audience_consent (id,contact_id,action,source,recorded_at,wording) VALUES ('.implode(',',[$q(substr(hash('sha256','consent|'.$prefix.$sourceId),0,32)),$q($id),$q('import_subscribed'),$q($prefix.$sourceId),$q(gmdate('Y-m-d H:i:s')),$q($wording)]).')');
  $saved=$rows('SELECT email,state FROM tbl_audience_contacts WHERE id='.$q($id));if(($saved[0]['email']??'')!==$row['email']||($saved[0]['state']??'')!=='subscribed')throw new RuntimeException('Import read-back failed');
 }
 $inside=$counts();foreach(['tbl_webinar_registrations','tbl_communications_outbox'] as $table)if($inside[$table]!==$before[$table])throw new RuntimeException('Import caused operational side effects');
 $exec($mode==='--apply'?'COMMIT':'ROLLBACK');if($mode!=='--apply'&&$counts()!==$before)throw new RuntimeException('Rehearsal rollback failed');
}catch(Throwable $error){$exec('ROLLBACK');throw $error;}
echo json_encode($summary+['export_sha256'=>hash_file('sha256',$path),'mail_created'=>0,'registrations_created'=>0,'rollback_verified'=>$mode!=='--apply'],JSON_THROW_ON_ERROR)."\n";
