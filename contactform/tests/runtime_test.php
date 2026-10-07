<?php
if(PHP_SAPI!=='cli'||getenv('CONTACT_TEST_SITE')!=='/var/www/html/ch')exit(64);
chdir(getenv('CONTACT_TEST_SITE'));$config=simplexml_load_file('config/config.xml');$host=parse_url((string)$config->KEWL_SITE_ROOT,PHP_URL_HOST);
if(!in_array($host,['chisimba.test','localhost','127.0.0.1'],true))throw new RuntimeException('Local only');
$_SERVER['REQUEST_METHOD']='CLI';$_SERVER['HTTP_HOST']=$host;$_SERVER['SCRIPT_NAME']='/index.php';$_SERVER['QUERY_STRING']='';$_SERVER['REMOTE_ADDR']='192.0.2.10';
$GLOBALS['kewl_entry_point_run']=true;require 'classes/core/engine_class_inc.php';$engine=new engine;
$engine->loadClass('contactservice','contactform');
class LocalContactService extends contactservice {public $fixtures;public function getObject($n,$m=''){return $this->fixtures[$n]??parent::getObject($n,$m);}}
class LocalContactConfig {function getValue($k,$m,$d=''){return ['CONTACT_MODE'=>'preview','CONTACT_RATE_KEY'=>str_repeat('q',32)][$k]??$d;}}
class LocalContactUser {function isLoggedIn(){return true;}function isAdmin(){return true;}function userId(){return 'contact-test';}}
class LocalContactGuard {function check($input){return '192.0.2.10';}}
$store=$engine->getObject('contactstore','contactform');$service=new LocalContactService;$service->objEngine=$engine;$service->fixtures=['contactstore'=>$store,'dbsysconfig'=>new LocalContactConfig,'user'=>new LocalContactUser,'contactguard'=>new LocalContactGuard];$service->init();
$ids=[bin2hex(random_bytes(16)),bin2hex(random_bytes(16))];$db=$engine->getDbObj();
function verifyContact($ok){if(!$ok)throw new RuntimeException('Contact database check failed');}
try{
 $input=['id'=>$ids[0],'name'=>'Reader','email'=>'contact-fixture@example.invalid','subject'=>'Synthetic contact test','message'=>'Please quote for five books.','website'=>''];
 verifyContact($service->submit($input,'')==='preview');verifyContact($service->submit($input,'')==='preview');
 $input['id']=$ids[1];$input['name']='AbCdEfGhIjKlMnOp';verifyContact($service->submit($input,'')==='sent');verifyContact($store->find($ids[1])['status']==='quarantined');
 $service->moderate([$ids[0]],'trash');$service->moderate([$ids[0]],'restore');
 $service->moderate([$ids[1]],'block');$hash=hash_hmac('sha256','sender|contact-fixture@example.invalid',str_repeat('q',32));verifyContact($store->blocked($hash));$service->moderate([$ids[1]],'unblock');verifyContact(!$store->blocked($hash));
 $engine->loadClass('submissioncontentpolicy','abuseprotection');verifyContact(SubmissionContentPolicy::suspiciousName('AbCdEfGhIjKlMnOp'));
 $evidence=$engine->getObject('contactguard','contactform')->evidence();verifyContact(strlen($evidence['signature'])===64);
 echo "PASS: Catalogue schema, actual storage, repeat submission, quarantine, trash/restore, block/unblock and shared guard loading\n";
}finally{
 foreach($ids as $id)foreach(['review','messages'] as $table)$db->exec('DELETE FROM tbl_contactform_'.$table.' WHERE id='.$db->quote($id));
 $rate=hash_hmac('sha256','192.0.2.10|'.floor(time()/3600),str_repeat('q',32));$db->exec('DELETE FROM tbl_contactform_limits WHERE id='.$db->quote($rate));
 if(isset($hash))$db->exec('DELETE FROM tbl_contactform_blocks WHERE id='.$db->quote($hash));
 echo "PASS: synthetic contact data removed; no mail sent\n";
}
