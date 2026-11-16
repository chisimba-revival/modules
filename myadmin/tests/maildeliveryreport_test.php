<?php
/** Run only against the dedicated disposable mail_report_test database. */
$dsn=getenv('MAIL_REPORT_TEST_DSN');
if(PHP_SAPI!=='cli'||!str_contains((string)$dsn,'dbname=mail_report_test'))exit(64);
$GLOBALS['kewl_entry_point_run']=true;
class dbTable {
 public static $db; public static $admin=true; public static $queries=0;
 public function getObject($name,$module){return new class {public function isAdmin(){return dbTable::$admin;}};}
 public function selectPrepared($sql,$values){self::$queries++;$s=self::$db->prepare($sql);$s->execute($values);return $s->fetchAll(PDO::FETCH_ASSOC);}
}
dbTable::$db=new PDO($dsn,getenv('MAIL_REPORT_TEST_USER'),getenv('MAIL_REPORT_TEST_PASSWORD'),[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
$db=dbTable::$db;
$db->exec('CREATE TEMPORARY TABLE tbl_communications_outbox(id varchar(32),recipient varchar(320),subject varchar(998),status varchar(24),channel varchar(32),transport varchar(32),attempts int,date_created datetime,sent_at datetime)');
$db->exec('CREATE TEMPORARY TABLE tbl_communications_attempts(id varchar(32),outbox_id varchar(32),attempt_number int,response_code int)');
$insert=$db->prepare('INSERT INTO tbl_communications_outbox VALUES(?,?,?,?,?,?,?,?,?)');
for($i=0;$i<53;$i++)$insert->execute([(string)$i,'person'.$i.'@example.invalid','Monday newsletter','sent','email','sendgrid',1,'2026-11-16 08:00:00','2026-11-16 08:01:00']);
$insert->execute(['waiting','waiting@example.invalid','Other','queued','email',null,0,'2026-11-16 09:00:00',null]);
$insert->execute(['failed','failed@example.invalid','Other','failed','email','sendgrid',3,'2026-11-16 09:00:00',null]);
$insert->execute(['late','late@example.invalid','100%_test','sent','email','sendgrid',1,'2026-11-15 23:59:00','2026-11-16 00:00:00']);
$insert->execute(['edge','edge@example.invalid','Old','sent','email','sendgrid',1,'2026-11-10 00:00:00','2026-11-10 00:00:00']);
$insert->execute(['excluded','no@example.invalid','Too old','sent','email','sendgrid',1,'2026-11-09 23:59:59','2026-11-09 23:59:59']);
$insert->execute(['tomorrow','no@example.invalid','Future','sent','email','sendgrid',1,'2026-11-17 00:00:00','2026-11-17 00:00:00']);
$db->exec("INSERT INTO tbl_communications_attempts VALUES('a','0',1,202),('b','failed',2,429),('c','failed',3,500)");
require dirname(__DIR__).'/classes/maildeliveryreport_class_inc.php';
function check($ok,$name){if(!$ok)throw new RuntimeException($name);}
$s=new maildeliveryreport();$now=new DateTimeImmutable('2026-11-16 12:00:00',new DateTimeZone('Africa/Johannesburg'));
$r=$s->report([],$now);check(count($r['days'])===7&&$r['days']['2026-11-16']===54&&$r['days']['2026-11-10']===1&&array_sum($r['days'])===55,'Seven-day sent boundaries and zero filling');check($r['total']===55&&count($r['rows'])===50&&$r['pages']===2,'Today date is queued date, pagination');
$r2=$s->report(['page'=>2],$now);check(count($r2['rows'])===5&&!array_intersect(array_column($r['rows'],'id'),array_column($r2['rows'],'id')),'Stable pages');
$r=$s->report(['search'=>'person0@example.invalid'],$now);check($r['total']===1&&$r['rows'][0]['response_code']==202,'Recipient and provider response');
$r=$s->report(['date'=>'','search'=>'%_'],$now);check($r['total']===1&&$r['rows'][0]['id']==='late','Literal wildcards and all dates');
$r=$s->report(['status'=>'failed'],$now);check($r['total']===1&&$r['rows'][0]['response_code']==500,'Last attempt and failed filter');
$r=$s->report(['search'=>"' OR 1=1 --"],$now);check($r['total']===0,'Bound search');
$r=$s->report(['date'=>"bad\0date",'status'=>'anything','page'=>999],$now);check($r['date']==='2026-11-16'&&$r['status']===''&&$r['page']===2,'Invalid filters and page clamp');
$before=dbTable::$queries;dbTable::$admin=false;
try{$s->report([],$now);throw new RuntimeException('Unauthorised access');}catch(DomainException $e){}
check($before===dbTable::$queries,'Permission check before data access');
echo "PASS: sent-day boundaries, zero days, queued-date totals, pagination, address/subject/status filters, literal wildcards, invalid input, latest response and revoked administrator access.\n";
