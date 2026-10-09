<?php
/** Disposable MariaDB integration test. Requires AUDIENCE_TEST_DSN/USER/PASSWORD; never uses application DB. */
if(!getenv('AUDIENCE_TEST_DSN')){fwrite(STDERR,"Set a disposable test DSN.\n");exit(64);}
$GLOBALS['kewl_entry_point_run']=true;
$pdo=new PDO(getenv('AUDIENCE_TEST_DSN'),getenv('AUDIENCE_TEST_USER'),getenv('AUDIENCE_TEST_PASSWORD'),[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
$db=$pdo->query('SELECT DATABASE()')->fetchColumn();if(!str_starts_with($db,'audience_test_'))throw new RuntimeException('Only isolated audience_test_ databases allowed');
define('MDB2_FETCHMODE_ASSOC',2);
class PEAR {static function isError($r){return false;}}
class Result {function __construct(private $stmt){}function fetchAll($mode){return $this->stmt->fetchAll(PDO::FETCH_ASSOC);}}
class Connection {function quote($v){return $GLOBALS['pdo']->quote($v);}function query($s){return new Result($GLOBALS['pdo']->query($s));}function exec($s){return $GLOBALS['pdo']->exec($s);}}
class ChisimbaObject {
 public $objEngine;
 function __construct(){$this->objEngine=new class{function getDbObj(){return new Connection;}};}
 function getObject($n,$m=null){return $GLOBALS['objects'][$n];}function loadClass($n,$m=null){}
}
class dbTable extends ChisimbaObject {
 private $table;
 function init($t=null,$p=null,$e=null){$this->table=$t;}
 function getRow($k,$v,$t=null){$st=$GLOBALS['pdo']->prepare('SELECT * FROM '.($t?:$this->table).' WHERE '.$k.'=?');$st->execute([$v]);return $st->fetch(PDO::FETCH_ASSOC)?:null;}
 function getArray($s){return $GLOBALS['pdo']->query($s)->fetchAll(PDO::FETCH_ASSOC);}
 function insert($r,$t=null){$st=$GLOBALS['pdo']->prepare('INSERT INTO '.($t?:$this->table).' ('.implode(',',array_keys($r)).') VALUES ('.implode(',',array_fill(0,count($r),'?')).')');return $st->execute(array_values($r));}
 function update($k,$v,$r,$t=null){$st=$GLOBALS['pdo']->prepare('UPDATE '.($t?:$this->table).' SET '.implode(',',array_map(fn($key)=>$key.'=?',array_keys($r))).' WHERE '.$k.'=?');return $st->execute([...array_values($r),$v]);}
 function query($s){return $GLOBALS['pdo']->exec($s);}function beginTransaction(){$GLOBALS['pdo']->exec('START TRANSACTION');}function commitTransaction(){$GLOBALS['pdo']->exec('COMMIT');}function rollbackTransaction(){$GLOBALS['pdo']->exec('ROLLBACK');}
}
require __DIR__.'/../classes/audienceadmin_class_inc.php';require __DIR__.'/../classes/audienceservice_class_inc.php';require __DIR__.'/../classes/audiencecampaigns_class_inc.php';require __DIR__.'/../classes/audiencecommunicationpolicy_class_inc.php';
$framework=getenv('AUDIENCE_TEST_FRAMEWORK');require $framework.'/app/core_modules/communications/classes/communicationservice_class_inc.php';require $framework.'/app/core_modules/communications/classes/communicationdeliverygate_class_inc.php';
function check($v,$why){if(!$v)throw new RuntimeException($why);}
function reject($fn,$code){try{$fn();throw new RuntimeException('Accepted '.$code);}catch(DomainException $e){check($e->getMessage()===$code,'Wrong failure '.$e->getMessage());}}
$schema=[__DIR__.'/../sql/tbl_audience_contacts.sql',__DIR__.'/../sql/tbl_audience_consent.sql',__DIR__.'/../sql/tbl_audience_tokens.sql',__DIR__.'/../sql/tbl_audience_campaigns.sql',$framework.'/app/core_modules/communications/sql/tbl_communications_outbox.sql'];
foreach($schema as $file){include $file;$cols=[];foreach($fields as $name=>$f){$type=match($f['type']){'integer'=>'INT','clob'=>'LONGTEXT','timestamp'=>'DATETIME',default=>isset($f['length'])?'VARCHAR('.$f['length'].')':'TEXT'};$cols[]='`'.$name.'` '.$type.(!empty($f['notnull'])?' NOT NULL':' NULL');}$pdo->exec('CREATE TABLE '.$tablename.' ('.implode(',',$cols).') ENGINE=InnoDB');}
foreach(['tbl_audience_contacts'=>['id','email'],'tbl_audience_campaigns'=>['id'],'tbl_communications_outbox'=>['id','idempotency_key']] as $table=>$cols)foreach($cols as $col)$pdo->exec("ALTER TABLE $table ADD UNIQUE KEY u_$col ($col)");
$user=new class{public $admin=true;function isLoggedIn(){return true;}function isAdmin(){return $this->admin;}function userId(){return 'fixture-admin';}};
$config=new class{function getValue($k,$m,$d=null){return ['COMMUNICATION_FROM_EMAIL'=>'sender@example.test','COMMUNICATION_FROM_NAME'=>'Test','COMMUNICATION_ALLOWED_RECIPIENTS'=>'','AUDIENCE_SUPPORT_MESSAGE'=>'Support fixture'][$k]??$d;}};
$GLOBALS['objects']=['user'=>$user,'dbsysconfig'=>$config,'altconfig'=>new class{function getSiteRoot(){return 'https://example.test/';}function getSiteName(){return 'Test';}},'language'=>new class{function code2Txt($k,$m){return 'Unsubscribe';}},'webinarannouncements'=>new class{public $recording='Recording fixture';function latestRecordingText(){return $this->recording;}function upcomingText($ids=null){return 'Public webinar details';}function upcomingHtml($ids=null){return '<section><img src="https://example.test/banner.jpg" width="250"><p><strong>Title:</strong> Public webinar details</p></section>';}function stillCurrent($p){return true;}}];
foreach(['audienceadmin','audienceservice','audiencecampaigns','communicationservice','communicationdeliverygate'] as $class){$o=new $class;$GLOBALS['objects'][$class]=$o;$o->init();}
$a=$GLOBALS['objects']['audienceadmin'];$contacts=$GLOBALS['objects']['audienceservice'];$campaigns=$GLOBALS['objects']['audiencecampaigns'];
$user->admin=false;reject(fn()=>$a->search('','',1),'forbidden');reject(fn()=>$campaigns->save('',[]),'forbidden');$user->admin=true;
$c=$contacts->contact('one@example.test','One');$c2=$contacts->contact('two@example.test','Two');
$input=['name'=>'One','email'=>'one@example.test','state'=>'subscribed','revision'=>0];reject(fn()=>$a->save($c['id'],$input),'consent_required');
$c=$a->save($c['id'],$input+['consent'=>'1','evidence'=>'Explicit fixture request']);check($c['state']==='subscribed','Consent saved');
reject(fn()=>$a->save($c['id'],$input),'conflict');
reject(fn()=>$a->save($c['id'],['name'=>'One','email'=>'two@example.test','state'=>'subscribed','revision'=>$c['revision']]),'duplicate');
check($a->search("' OR 1=1 --",'',1)['count']===0,'Search remains quoted');
$id=bin2hex(random_bytes(16));$draft=$campaigns->save('',['create_id'=>$id,'subject'=>'Fixture','greeting'=>'Hello {FIRSTNAME},','body'=>'A message','upcoming'=>'1','support'=>'1']);
check(str_contains($campaigns->compose($draft),'Public webinar details')&&str_contains($campaigns->compose($draft),'Support fixture'),'Optional content assembled');
$plain=$campaigns->save('', ['create_id'=>bin2hex(random_bytes(16)),'subject'=>'Plain','body'=>'Only text']);check($campaigns->compose($plain)==='Only text','Optional content omitted');
$recorded=$campaigns->save('', ['create_id'=>bin2hex(random_bytes(16)),'subject'=>'Recording','greeting'=>'Hello,','latest_recording'=>'1','body'=>'My message']);
check($campaigns->compose($recorded)==="Hello,\n\nRecording fixture\n\nMy message",'Recording follows greeting before message');
$GLOBALS['objects']['webinarannouncements']->recording='';
check($campaigns->compose($recorded)==="Hello,\n\nRecording fixture\n\nMy message",'Reviewed preview stays frozen');
$without=$campaigns->save('', ['create_id'=>bin2hex(random_bytes(16)),'subject'=>'No recording','greeting'=>'Hello,','latest_recording'=>'1','body'=>'My message']);
check($campaigns->compose($without)==="Hello,\n\nMy message",'Unavailable recording leaves no empty section');
foreach(['Derek Keats'=>'Derek',''=>'there',"  \t \n"=>'there','Élodie Smith'=>'Élodie','Anne-Marie Smith'=>'Anne-Marie',"O’Connor Smith"=>"O’Connor",'person@example.test'=>'there'] as $name=>$expected){check(audiencecampaigns::personalise('Hello {FIRSTNAME},',['name'=>$name])==='Hello '.$expected.',','Personalised name and fallback');}
check(str_starts_with($campaigns->compose($draft),'Hello {FIRSTNAME},'),'Saved preview retains recipient placeholder');
$q=$campaigns->queue($id,1);$p=json_decode($q['payload'],true);check(count($p['recipients'])===1,'Only subscribed recipients');
$campaigns->queue($id,1);check($campaigns->one($id)['version']===$q['version'],'Repeated queue no mutation');
reject(fn()=>$campaigns->save($id,['subject'=>'Changed','body'=>'x','version'=>$q['version']]),'conflict');
$campaigns->pump(1);check((int)$pdo->query('SELECT COUNT(*) FROM tbl_communications_outbox')->fetchColumn()===1,'Exactly one queued message');
$mail=$pdo->query('SELECT * FROM tbl_communications_outbox')->fetch(PDO::FETCH_ASSOC);check(str_contains($mail['body_html'],'action=unsubscribe')&&str_contains($mail['body_html'],'Hello One,')&&str_contains($mail['body_html'],'<img'),'HTML queued with personalised greeting, banner and unsubscribe');check(str_contains($mail['body_text'],'action=unsubscribe'),'Personal unsubscribe included');check(str_starts_with($mail['body_text'],"Hello One,\n\n"),'Personal greeting in queued recipient email');$meta=json_decode($mail['metadata_json'],true);
$policy=new audiencecommunicationpolicy;check($policy->allows($meta),'Current consent allowed');
$GLOBALS['objects']['modules']=new class {function checkIfRegistered($m){return true;}};$GLOBALS['objects']['audiencecommunicationpolicy']=$policy;$GLOBALS['objects']['communicationpolicy']=new class{function allows($m){return false;}};
$gate=$GLOBALS['objects']['communicationdeliverygate'];check($gate->allows($mail),'Module-owned policy coexists with webinar policy');$bad=$mail;$bad['metadata_json']=json_encode($meta+['unused'=>1]);$badMeta=$meta;$badMeta['policy_class']='otherpolicy';$bad['metadata_json']=json_encode($badMeta);check(!$gate->allows($bad),'Unknown policy class rejected');
$campaigns->pump(20);check((int)$pdo->query('SELECT COUNT(*) FROM tbl_communications_outbox')->fetchColumn()===1,'Retry no duplicate');
$contacts->unsubscribe($contacts->unsubscribeToken($c['id']));check(!$policy->allows($meta),'Unsubscribe blocks already queued delivery');
$c=$contacts->one($c['id']);$a->save($c['id'],['name'=>'One','email'=>'one@example.test','state'=>'subscribed','revision'=>$c['revision'],'consent'=>'1','evidence'=>'New fixture consent']);check(!$policy->allows($meta),'Resubscription cannot revive old queued message');
$r=$campaigns->one($id);$campaigns->cancel($id,$r['version']);check(!$policy->allows($meta),'Cancellation blocks delivery');
check((int)$pdo->query('SELECT COUNT(*) FROM tbl_audience_consent')->fetchColumn()>=3,'Consent audit retained');
$oldToken=$contacts->unsubscribeToken($c['id']);$c=$contacts->one($c['id']);$changed=$a->save($c['id'],['name'=>'One','email'=>'new@example.test','state'=>'subscribed','revision'=>$c['revision'],'consent'=>'1','evidence'=>'New address consent']);check($contacts->contactForToken($oldToken)===null,'Old mailbox cannot unsubscribe the replacement address');
echo "PASS database integration: permissions, consent, conflicts, duplicates, optional content, frozen recipients, resumable queue, unsubscribe and cancellation. No email delivered.\n";
