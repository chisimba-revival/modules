<?php
/** Synthetic MariaDB count and rendering checks; no application or mail delivery. @author Derek Keats */
if(PHP_SAPI!=='cli')exit(1);
$GLOBALS['kewl_entry_point_run']=true;
class ChisimbaObject {
 public function getObject($name,$module=''){return $GLOBALS['services'][$name];}
 public function loadClass($name,$module=''){}
 public function uri($params,$module=''){return '/index.php?'.http_build_query(['module'=>$module]+$params);}
}
class dbTable extends ChisimbaObject {
 public $queries=0;
 public function getArray($sql){$this->queries++;return $GLOBALS['db']->query($sql)->fetchAll(PDO::FETCH_ASSOC);}
}
foreach(['webinarschedule','webinareditpolicy','webinarregistrations','webinarrenderer','helpcontent'] as $class)require dirname(__DIR__).'/classes/'.$class.'_class_inc.php';
function check($ok,$why){if(!$ok)throw new RuntimeException($why);}
$dsn=getenv('WEBINAR_COUNT_TEST_DSN');
if(!$dsn||!str_contains($dsn,'dbname=webinar_count_test'))throw new RuntimeException('Use an isolated webinar_count_test database.');
$db=new PDO($dsn,getenv('WEBINAR_COUNT_TEST_USER')?:'root',getenv('WEBINAR_COUNT_TEST_PASSWORD'),[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
// Temporary tables shadow any persistent names, and disappear with this connection.
$db->exec('CREATE TEMPORARY TABLE tbl_webinar_registrations (webinar_id VARCHAR(32), contact_id VARCHAR(32), state VARCHAR(16), contact_revision INT)');
$db->exec('CREATE TEMPORARY TABLE tbl_audience_contacts (id VARCHAR(32), state VARCHAR(16), revision INT)');
$user=new class {public $logged=true,$admin=true,$id='creator';function isLoggedIn(){return $this->logged;}function isAdmin(){return $this->admin;}function userId(){return $this->id;}};
$permissions=new class {function areaIdForName($a,$b){return 'webinar';}function rightIdForArea($a,$b){return 'manage';}function isGranted($a,$b){return true;}};
$texts=[];foreach(file(dirname(__DIR__).'/register.conf') as $line)if(str_starts_with($line,'TEXT: ')){[$key,$description,$value]=explode('|',trim(substr($line,6)),3);$texts[$key]=str_replace(['[-webinar-]','[-webinars-]'],['webinar','webinars'],$value);}
$language=new class($texts){function __construct(public $texts){}function code2Txt($key,$module){return $this->texts[$key]??$key;}};
$icons=new class{function render($name,$options=[]){return '<span aria-hidden="true">✎</span>';}};
$policy=new webinareditpolicy();$registrations=new webinarregistrations();$renderer=new webinarrenderer();
$GLOBALS['services']=['user'=>$user,'permissionservice'=>$permissions,'webinareditpolicy'=>$policy,'webinarregistrations'=>$registrations,'webinarrenderer'=>$renderer,'language'=>$language,'iconservice'=>$icons];
function row($id,$creator='creator') {return ['id'=>str_repeat($id,32),'title'=>'Bird webinar '.$id,'kind'=>'webinar','status'=>'published','presented_at'=>'2099-10-15 19:00:00','payload'=>json_encode(['timezone'=>'Africa/Johannesburg','ends_at'=>'2099-10-15 20:00:00','created_by'=>$creator,'speakers'=>[]])];}
$a=row('a');$b=row('b','another');$zero=row('c');$legacy=row('d',null);$past=row('e');$past['presented_at']='2000-01-01';$past['payload']=json_encode(['timezone'=>'UTC','created_by'=>'creator']);$draft=row('f');$draft['status']='draft';$speaker=row('1');$speaker['kind']='speaker';
$contact=$db->prepare('INSERT INTO tbl_audience_contacts VALUES (?,?,?)');$registration=$db->prepare('INSERT INTO tbl_webinar_registrations VALUES (?,?,?,?)');
foreach([['one','subscribed',1,'confirmed',1],['pending','subscribed',1,'pending',1],['withdrawn','unsubscribed',2,'confirmed',2],['stale','subscribed',2,'confirmed',1],['two','subscribed',3,'confirmed',3]] as [$id,$state,$revision,$booking,$bookingRevision]){$contact->execute([$id,$state,$revision]);$registration->execute([$a['id'],$id,$booking,$bookingRevision]);}
$registration->execute([$a['id'],'one','confirmed',1]); // Count people, even if an old duplicate exists.
$registration->execute([$a['id'],'missing-contact','confirmed',1]);
$registration->execute([$b['id'],'one','confirmed',1]);
$rows=[$a,$b,$zero,$legacy,$past,$draft,$speaker];
check($registrations->countsForUpcoming($rows)===[$a['id']=>2,$b['id']=>1,$zero['id']=>0,$legacy['id']=>0],'Admin counts; exclude pending, withdrawn, stale, duplicate and missing contacts');
check($registrations->queries===1,'One aggregate query per page');
$admin=$renderer->cards([$a,$b,$zero,$legacy],true);check(substr_count($admin,'chisimba-pill')===4&&str_contains($admin,'0 registered'),'Admin badges including zero');
$user->admin=false;
check($registrations->countsForUpcoming($rows)===[$a['id']=>2,$zero['id']=>0],'Creator sees own only, despite broad manage permission');
$creator=$renderer->cards([$a,$b,$zero,$legacy],true);check(substr_count($creator,'chisimba-pill')===2,'No unrelated totals in creator HTML');
$user->id='outsider';$before=$registrations->queries;check($registrations->countsForUpcoming($rows)===[],'Unrelated manager denied');check($registrations->queries===$before,'No unauthorised query');
$outsider=$renderer->cards([$a,$b,$zero],true);check(!str_contains($outsider,'registered'),'No count text leaked');
$user->logged=false;$user->admin=true;check($registrations->countsForUpcoming($rows)===[],'Logged-out stale admin denied');$anonymous=$renderer->cards([$a,$b,$zero],true);check(!str_contains($anonymous,'registered'),'Anonymous HTML omits counts');
$user->logged=true;$user->admin=true;check(!str_contains($renderer->cards([$a]),'registered'),'Non-upcoming callers retain no-count default');
$db->exec("UPDATE tbl_audience_contacts SET state='unsubscribed' WHERE id='two'");check($registrations->countsForUpcoming([$a])[$a['id']]===1,'Refreshed count reflects withdrawal');
$bad=$a;$bad['id']="' OR 1=1";$before=$registrations->queries;check($registrations->countsForUpcoming([$bad])===[]&&$registrations->queries===$before,'Malformed IDs rejected before query');
$help=new helpcontent();check($help->getTopic('registration_counts')['title']==='Registration counts','Registered contextual Help');$user->logged=false;check($help->getTopic('registration_counts')===null,'Private Help hidden from guests');
if($dir=getenv('WEBINAR_COUNT_HTML_DIR'))foreach(compact('admin','creator','outsider','anonymous') as $role=>$html)file_put_contents($dir.'/'.$role.'.html','<!doctype html><html lang="en"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Webinar count — '.$role.'</title><link rel="stylesheet" href="/skin/stylesheet.css"><link rel="stylesheet" href="/skin/canvases/_default/stylesheet.css"><main class="chisimba-form-card chisimba-form-card--wide"><h1>Upcoming webinars</h1>'.$html.'</main></html>');
echo "PASS: MariaDB counts, duplicate/pending/withdrawn/stale contacts, zero, admin/creator/outsider/guest permissions, HTML disclosure, refresh, contextual Help and query batching.\n";
