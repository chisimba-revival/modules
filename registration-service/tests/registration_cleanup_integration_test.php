<?php
/** Run only in the disposable registration_guard_test database; never against a live site. */
if (PHP_SAPI!=='cli' || getenv('REGISTRATION_GUARD_TEST')!=='1') { fwrite(STDERR,"Disposable database opt-in required.\n");exit(64); }
chdir('/var/www/html/ch');$_SERVER['REQUEST_METHOD']='CLI';$_SERVER['HTTP_HOST']='localhost:8086';$_SERVER['SCRIPT_NAME']='/ch/index.php';$_SERVER['QUERY_STRING']='';$GLOBALS['kewl_entry_point_run']=true;
require 'classes/core/engine_class_inc.php';$engine=new engine();
if(!str_ends_with(KEWL_DB_DSN,'/registration_guard_test'))throw new RuntimeException('Wrong database');
$db=$engine->getDbObj();
function sql($query,$args=[],$read=true) { global $db; $s=$db->prepare($query,null,$read?MDB2_PREPARE_RESULT:MDB2_PREPARE_MANIP);if(PEAR::isError($s))throw new RuntimeException('prepare');$r=$s->execute($args);$s->free();if(PEAR::isError($r))throw new RuntimeException($r->getMessage());if(!$read)return $r;$rows=$r->fetchAll(MDB2_FETCHMODE_ASSOC);$r->free();return $rows; }
function check($ok,$message) { if(!$ok)throw new RuntimeException($message); }
$service=$engine->getObject('registrationservice','registration-service');$cleanup=$engine->getObject('registrationcleanup','registration-service');$guard=$engine->getObject('registrationguard','registration-service');$ids=[];
function pending($name='Synthetic') { global $service,$ids; $key='guard'.bin2hex(random_bytes(6));$r=$service->createPending(['username'=>$key,'emailAddress'=>$key.'@example.test','firstName'=>$name,'surname'=>'Test','countryCallingCode'=>'27','mobileNumber'=>'0821234567','identityDocumentType'=>'passport','identityDocumentNumber'=>'SYNTHETIC','password'=>'Registration-Fixture-2026!','correlationId'=>$key]);check(!empty($r['ok']),'create pending: '.($r['code']??''));$ids[]=$r['pendingId'];return $r['pendingId']; }
$canonicalBefore=sql('CHECKSUM TABLE tbl_users');
try {
    check($guard->suspiciousName('AfLxMnOwvslpOvVtkyZKT','Sachchgzy'),'provided random name flagged');
    foreach(['Derek','Christopher','Nkosinathi','María-José','张伟','McDonald','ALEXANDER','Mpho'] as $name)check(!$guard->suspiciousName($name),'ordinary and international names not flagged');
    $live=pending();$old=pending();$paid=pending();$linked=pending();$verified=pending();$dismiss=pending();$matching=pending();
    $row=sql('SELECT * FROM tbl_registration_service_pending WHERE id=?',[$live])[0];
    check(abs(strtotime($row['expires_at'])-time()-7*86400)<5,'seven day lifetime');
    foreach([$old,$paid,$linked,$verified,$matching] as $id)sql('UPDATE tbl_registration_service_pending SET expires_at=? WHERE id=?',[date('Y-m-d H:i:s',time()-86400),$id],false);
    sql("UPDATE tbl_registration_service_pending SET payment_product_code='synthetic-protected' WHERE id=?",[$paid],false);
    sql("UPDATE tbl_registration_service_pending SET provisioned_user_id='1' WHERE id=?",[$linked],false);
    sql("UPDATE tbl_registration_service_pending SET status='verified',verified_at=NOW() WHERE id=?",[$verified],false);
    $admin=$engine->getObject('userservice','security')->findByUserId('1');
    sql('UPDATE tbl_registration_service_pending SET username=? WHERE id=?',[$admin['username'],$matching],false);
    try { $cleanup->dismiss($dismiss);throw new RuntimeException('Anonymous dismissal allowed'); } catch(RuntimeException $ex) { check($ex->getMessage()==='Administrator access required.','anonymous service boundary'); }
    $stack=$engine->getObject('nativeauthwebcomposition','security')->build();check($stack['sessions']->establish('1',['username'=>$admin['username']]),'test session');
    check($cleanup->dismiss($paid)==='protected','payment protected');
    check($cleanup->dismiss($linked)==='protected','linked account protected');
    check($cleanup->dismiss($verified)==='protected','verified protected');
    check($cleanup->dismiss($matching)==='protected','canonical username match protected');
    sql("UPDATE tbl_registration_service_pending SET status='awaiting_verification' WHERE id=?",[$dismiss],false);
    $token=$engine->getObject('registrationtokenservice','registration-service')->issue('email_verification','pending_registration',$dismiss,'guard.fixture',86400);
    check(!empty($token['ok']),'test verification token');
    check($cleanup->dismiss($dismiss)==='changed','manual dismissal');
    check($cleanup->dismiss($dismiss)==='unavailable','duplicate dismissal idempotent');
    check(empty($service->completeEmailVerification($token['rawToken'])['ok']),'dismissed token cannot verify');
    $preview=$cleanup->preview();check($preview['expire']>=1,'preview finds expired');
    check(sql('SELECT status FROM tbl_registration_service_pending WHERE id=?',[$old])[0]['status']==='awaiting_legal_acceptance','preview read only');
    $result=$cleanup->run();check($result['expired']>=1 && !$result['failed'],'cleanup run');
    check(sql('SELECT status FROM tbl_registration_service_pending WHERE id=?',[$live])[0]['status']==='awaiting_legal_acceptance','live request retained');
    check(sql('SELECT status,password_hash FROM tbl_registration_service_pending WHERE id=?',[$old])[0]===['status'=>'expired','password_hash'=>null],'expired request credential cleared');
    sql('UPDATE tbl_registration_service_pending SET updated_at=? WHERE id IN (?,?)',[date('Y-m-d H:i:s',time()-31*86400),$old,$dismiss],false);
    check($cleanup->run()['redacted']>=2,'retention redaction');
    $row=sql('SELECT * FROM tbl_registration_service_pending WHERE id=?',[$dismiss])[0];
    foreach(['first_name','surname','username','email_address','mobile_number','identity_document_type','identity_document_number'] as $key)check((string)$row[$key]==='','PII cleared '.$key);
    check($cleanup->run()['redacted']===0,'repeat cleanup idempotent');
    $events=sql("SELECT event_type,actor_type FROM tbl_account_event_service_events WHERE subject_id=? AND event_type IN ('registration.dismissed','registration.redacted') ORDER BY event_type",[$dismiss]);
    check(count($events)===2 && $events[0]['actor_type']==='user' && $events[1]['actor_type']==='service','audited actors');
    check($canonicalBefore===sql('CHECKSUM TABLE tbl_users'),'canonical accounts unchanged');
    echo "PASS: creation, permissions, protected states, token invalidation, expiry, redaction, repeat runs and audit\n";
} finally {
    foreach($ids as $id) { sql('DELETE FROM tbl_registration_service_tokens WHERE subject_type=? AND subject_id=?',['pending_registration',$id],false);sql('DELETE FROM tbl_registration_service_pending WHERE id=?',[$id],false);sql('DELETE FROM tbl_account_event_service_events WHERE subject_type=? AND subject_id=?',['pending_registration',$id],false); }
}
