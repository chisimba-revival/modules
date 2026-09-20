<?php
$GLOBALS['kewl_entry_point_run']=true;
class ChisimbaObject {public static $objects=[];public function getObject($name,$module=null){return self::$objects[$name];}public function loadClass($name,$module=null){}}
require dirname(__DIR__).'/classes/webinarschedule_class_inc.php';
require dirname(__DIR__).'/classes/communicationpolicy_class_inc.php';
$n=0;function check($ok,$label){global $n;if(!$ok)throw new RuntimeException($label);$n++;}
$start=new DateTimeImmutable('2030-09-21 19:00:00',new DateTimeZone('Africa/Johannesburg'));
$r=['kind'=>'webinar','status'=>'published','presented_at'=>'2030-09-21 19:00:00','payload'=>json_encode(['timezone'=>'Africa/Johannesburg','registration_open'=>true])];
check(webinarschedule::canRegister($r,$start->getTimestamp()-1),'Open before start');
check(!webinarschedule::canRegister($r,$start->getTimestamp()),'Closed at start');
check(!webinarschedule::canRegister($r,$start->getTimestamp()+1),'Closed after start');
foreach(['draft','cancelled'] as $v){$t=$r;$t['status']=$v;check(!webinarschedule::canRegister($t),'Unpublished closed');}
foreach([['timezone'=>'bad'],['timezone'=>'Africa/Johannesburg','registration_open'=>false],['timezone'=>'Africa/Johannesburg','registration_open'=>true,'cancelled'=>true]] as $v){$t=$r;$t['payload']=json_encode($v);check(!webinarschedule::canRegister($t),'Invalid or cancelled closed');}
check(webinarschedule::reminderDue($r,'morning')===$start->setTime(8,0)->getTimestamp(),'Morning in event zone');
check(webinarschedule::reminderDue($r,'ninety')===$start->getTimestamp()-5400,'Ninety minutes');
check(webinarschedule::reminderDue($r,'unknown')===null,'Unknown reminder');
class RowStore{public $row;function one($id){return $this->row;}}
$regs=new RowStore;$contacts=new RowStore;$records=new RowStore;
ChisimbaObject::$objects=['webinarregistrations'=>$regs,'audienceservice'=>$contacts,'webinarstore'=>$records];
$regs->row=['state'=>'pending','expires_at'=>time()+1000,'contact_id'=>'x','webinar_id'=>'y','confirm_hash'=>hash('sha256','test')];$contacts->row=['state'=>'pending','revision'=>0];$records->row=$r;
$m=['registration_id'=>'r','kind'=>'verify','revision'=>0,'confirm_hash'=>hash('sha256','test'),'starts_at'=>$start->getTimestamp()];$p=new communicationpolicy;
check($p->allows($m),'Pending verification permitted');
$t=$m;$t['confirm_hash']='bad';check(!$p->allows($t),'Stale verification denied');
$contacts->row['revision']=1;check(!$p->allows($m),'Unsubscribe revision invalidates verification');
$contacts->row=['state'=>'subscribed','revision'=>0];$regs->row['state']='confirmed';
check(!$p->allows($m),'Verified link not remailed');
foreach(['confirmed','monday','morning','ninety'] as $kind){$m['kind']=$kind;check($p->allows($m),'Confirmed subscriber mail permitted');$contacts->row['state']='unsubscribed';check(!$p->allows($m),'Unsubscribe suppresses '.$kind);$contacts->row['state']='subscribed';}
foreach(['confirmed','monday','morning','ninety'] as $kind){$m['kind']=$kind;$m['starts_at']=$start->getTimestamp()+1;check(!$p->allows($m),'Rescheduled '.$kind.' suppressed');}
$m['kind']='ninety';$m['starts_at']++;check(!$p->allows($m),'Rescheduled reminder suppressed');
$m['starts_at']=$start->getTimestamp();$records->row['payload']=json_encode(['timezone'=>'Africa/Johannesburg','registration_open'=>true,'cancelled'=>true]);check(!$p->allows($m),'Cancelled webinar reminder suppressed');
foreach(['confirmed','monday','morning','ninety'] as $kind){$m['kind']=$kind;check(!$p->allows($m),'Cancelled '.$kind.' suppressed');}
$regs->row=null;check(!$p->allows($m),'Missing registration denied');
echo "$n registration schedule and delivery-policy checks passed\n";
