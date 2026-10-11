<?php
/** Real dbTable/audience registration transactions against a disposable database only.
 * Run with run-capacity-tests.py. No mail transport is loaded. @author Derek Keats
 */
if(PHP_SAPI!=='cli'||getenv('WEBINAR_CAPACITY_FIXTURE')!=='1')exit(64);
require '/framework/tests/core/strict_warning_bootstrap.php';
$GLOBALS['kewl_entry_point_run']=true;
set_include_path('/framework/app'.PATH_SEPARATOR.'/framework/app/lib/pear'.PATH_SEPARATOR.get_include_path());
class ChisimbaObject {
 public function getObject($name,$module=null){return $GLOBALS['services'][$name];}
 public function loadClass($name,$module=null){}
}
require '/framework/app/classes/core/dbtable_class_inc.php';
$engine=getenv('WEBINAR_TEST_ENGINE')?:'PDO';
$dsn=['phptype'=>'mysqli','hostspec'=>'baseline-db','username'=>'root','password'=>getenv('CHISIMBA_DB_PASSWORD'),'database'=>'contract_fixture'];
if($engine==='MDB2'){
 require 'MDB2.php';$db=MDB2::connect($dsn);if(PEAR::isError($db))throw new RuntimeException('Connection failed');
 $db->setFetchMode(MDB2_FETCHMODE_ASSOC);$db->setOption('portability',MDB2_PORTABILITY_ALL);$db->setCharset('utf8');
}else $db=ChisimbaDatabaseConnection::fromLegacyDsn($dsn);
foreach(['webinarschedule','webinarregistrations','webinarregistrationservice','webinareditpolicy'] as $class)require __DIR__.'/../classes/'.$class.'_class_inc.php';
require __DIR__.'/../../audience/classes/audienceservice_class_inc.php';
function repository($class,$table){global $db,$engine;$r=new $class();(new ReflectionProperty(dbTable::class,'_db'))->setValue($r,$db);$r->dbLayer=$engine;$r->dbType='mysqli';$r->_tableName=$table;$r->_serverName='fixture';return $r;}
function check($ok,$message){if(!$ok)throw new RuntimeException($message);}
function rejects($fn,$message){try{$fn();}catch(DomainException $e){check($e->getMessage()===$message,'Wrong rejection '.$e->getMessage());return;}throw new RuntimeException('Expected '.$message);}
class FixtureService extends webinarregistrationservice {
 public $fail=false;
 public function queue(array $r,array $w,array $c,$kind,$token=''){
  // A transactional stand-in for the communications outbox; never send mail.
  $GLOBALS['table']->insert(['id'=>bin2hex(random_bytes(16)),'kind'=>$kind,'token'=>$token,'registration_id'=>$r['id']],'fixture_outbox');
  if($this->fail)throw new RuntimeException('Synthetic outbox failure');
  if(getenv('WEBINAR_RACE_DELAY'))usleep(300000);
 }
}
$table=repository('dbTable','tbl_webinar_records');$regs=repository('webinarregistrations','tbl_webinar_registrations');$audience=repository('audienceservice','tbl_audience_contacts');
$config=new class{public $verify=false;function getValue($a,$b){return $this->verify?'TRUE':'FALSE';}};
$user=new class{public $logged=true,$admin=true;function isLoggedIn(){return $this->logged;}function isAdmin(){return $this->admin;}function userId(){return 'owner';}};
$GLOBALS['services']=['audienceservice'=>$audience,'webinarregistrations'=>$regs,'webinarstore'=>$table,'communicationservice'=>new stdClass(),'dbsysconfig'=>$config,'webinarrenderer'=>new class{function text($k){return $k;}},'user'=>$user,'webinareditpolicy'=>new webinareditpolicy()];
$service=new FixtureService();$service->init();$id=str_repeat('a',32);
function event($limit=1){global $id;return ['id'=>$id,'kind'=>'webinar','status'=>'published','title'=>'Synthetic capacity test','presented_at'=>'2099-01-01 19:00:00','payload'=>json_encode(['timezone'=>'UTC','registration_open'=>true,'registration_limit'=>$limit,'created_by'=>'owner'])];}
function register($email){global $service;return $service->register(event(),'Synthetic person',$email,true,$email);}
function resetFixture($limit=1){global $table;foreach(['tbl_webinar_registrations','tbl_audience_contacts','tbl_audience_consent','tbl_audience_limits','fixture_outbox','tbl_webinar_records'] as $name)$table->executePrepared('DELETE FROM '.$name);$table->insert(event($limit));}
