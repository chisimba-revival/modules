<?php
/** Isolated temporary-table regression test; no mail or production data. */
$GLOBALS['kewl_entry_point_run']=true;
class ChisimbaObject {function getObject($name,$module=''){return $GLOBALS['services'][$name];}function loadClass($name,$module=''){} }
class dbTable extends ChisimbaObject {public $queries=0;function selectPrepared($sql,$args=[]){$this->queries++;$s=$GLOBALS['db']->prepare($sql);$s->execute($args);return $s->fetchAll(PDO::FETCH_ASSOC);} }
foreach(['webinarattendees','webinarregistrations','webinareditpolicy','webinarschedule'] as $name)require dirname(__DIR__).'/classes/'.$name.'_class_inc.php';
require getenv('ATTENDEE_FRAMEWORK').'/core_modules/abuseprotection/classes/submissioncontentpolicy_class_inc.php';
$dsn=getenv('ATTENDEE_TEST_DSN');if(!$dsn||!str_contains($dsn,'dbname=webinar_attendees_test'))throw new RuntimeException('Disposable database required');
$db=new PDO($dsn,getenv('ATTENDEE_TEST_USER'),getenv('ATTENDEE_TEST_PASSWORD'),[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
$db->exec('CREATE TEMPORARY TABLE tbl_audience_contacts(id VARCHAR(32),name VARCHAR(200),email VARCHAR(254),state VARCHAR(16),revision INT)');
$db->exec('CREATE TEMPORARY TABLE tbl_webinar_registrations(id VARCHAR(32),webinar_id VARCHAR(32),contact_id VARCHAR(32),state VARCHAR(16),contact_revision INT,confirmed_at VARCHAR(32))');
$id=str_repeat('a',32);$record=['id'=>$id,'kind'=>'webinar','status'=>'published','payload'=>json_encode(['created_by'=>'owner'])];
$user=new class {public $logged=true,$admin=true,$id='owner';function isLoggedIn(){return $this->logged;}function isAdmin(){return $this->admin;}function userId(){return $this->id;}};
$permissions=new class {public $allowed=true;function areaIdForName(...$a){return 1;}function rightIdForArea(...$a){return 1;}function isGranted(...$a){return $this->allowed;}};
$store=new class {function record($id){return $id===$GLOBALS['id']?$GLOBALS['record']:null;}};
$regs=new webinarregistrations();$services=['user'=>$user,'permissionservice'=>$permissions,'webinareditstore'=>$store,'webinareditpolicy'=>new webinareditpolicy(),'webinarregistrations'=>$regs];
$c=$db->prepare('INSERT INTO tbl_audience_contacts VALUES(?,?,?,?,?)');$r=$db->prepare('INSERT INTO tbl_webinar_registrations VALUES(?,?,?,?,?,?)');
for($i=0;$i<173;$i++){$key=sprintf('%032d',$i);$c->execute([$key,$i===0?'AfLxMnOwvslpOvVtkyZKT':'Person '.sprintf('%03d',$i),$i===1?'invalid-address':"person$i@example.invalid",$i===170?'unsubscribed':'subscribed',1]);$r->execute([$key,$id,$key,$i===171?'pending':'confirmed',$i===172?0:1,'2026-11-11 08:00:00']);}
$r->execute([str_repeat('f',32),$id,sprintf('%032d',0),'confirmed',1,'2026-11-11 08:01:00']);
function check($ok,$label){if(!$ok)throw new RuntimeException($label);}
$s=new webinarattendees();$first=$s->listing($id);check($first['total']===170&&count($first['rows'])===50&&$first['pages']===4,'Distinct eligible contacts, bounded pages');
check($first['rows'][0]['review']==='attendees_generated_name','Shared name review hint');check($first['rows'][1]['review']==='attendees_invalid_email','Malformed email hint');check($first['rows'][2]['review']==='','Ordinary names unflagged');
$last=$s->listing($id,999);check(count($last['rows'])===20&&$last['page']===4,'Last page and clamp');
$all=[];for($p=1;$p<=4;$p++)foreach($s->listing($id,$p)['rows'] as $row)$all[]=$row['email'];check(count(array_unique($all))===170,'No duplication or omissions');
$user->admin=false;check($s->listing($id)['total']===170,'Authorised creator');
foreach(['outsider','revoked','guest','missing'] as $case){$user->id=$case==='outsider'?'other':'owner';$user->logged=$case!=='guest';$permissions->allowed=$case!=='revoked';$before=$regs->queries;try{$s->listing($case==='missing'?'bad-id':$id);throw new RuntimeException('Access allowed '.$case);}catch(DomainException $e){check($regs->queries===$before,'Denied before attendee query '.$case);}}
echo "PASS: 170 eligible attendees, duplicate/pending/unsubscribed/stale exclusions, pagination, review hints and admin/creator/outsider/revoked/guest permissions.\n";
