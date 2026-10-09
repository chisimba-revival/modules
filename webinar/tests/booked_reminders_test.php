<?php
$GLOBALS['kewl_entry_point_run']=true;
class ChisimbaObject {function getObject($n,$m=null){return $GLOBALS['objects'][$n];}function loadClass($n,$m=null){}}
require __DIR__.'/../classes/webinarschedule_class_inc.php';require __DIR__.'/../classes/webinarregistrationservice_class_inc.php';
function check($v,$message){if(!$v)throw new RuntimeException($message);}
function ts($s){return (new DateTimeImmutable($s,new DateTimeZone('Africa/Johannesburg')))->getTimestamp();}
$record=['id'=>'event','kind'=>'webinar','status'=>'published','presented_at'=>'2030-09-19 19:00:00','payload'=>json_encode(['timezone'=>'Africa/Johannesburg','registration_open'=>true])];
$audience=new class {public $rows=[];function one($id){return $this->rows[$id]??null;}};
$regs=new class{public $rows=[];function confirmed($id){return array_filter($this->rows,fn($r)=>$r['state']==='confirmed'&&$r['webinar_id']===$id);}};
foreach(['active','unsubscribed','stale','late','pending'] as $id){$audience->rows[$id]=['id'=>$id,'state'=>$id==='unsubscribed'?'unsubscribed':'subscribed','revision'=>$id==='stale'?1:0];$regs->rows[]=['id'=>$id,'contact_id'=>$id,'webinar_id'=>'event','state'=>$id==='pending'?'pending':'confirmed','contact_revision'=>0,'confirmed_at'=>$id==='late'?'2030-09-16 07:00:00':'2030-09-01 00:00:00'];}
$records=new class($record){function __construct(public $record){}function published($kind){return [$this->record];}};
$GLOBALS['objects']=['audienceservice'=>$audience,'webinarregistrations'=>$regs,'webinarstore'=>$records,'communicationservice'=>new stdClass];
class ReminderProbe extends webinarregistrationservice {public $queued=[];function queue(array $r,array $w,array $c,$kind,$token=''){$this->queued[]=$r['id'].':'.$kind;}}
$s=new ReminderProbe;$s->init();$s->reminders(ts('2030-09-16 08:01'));
check($s->queued===['active:monday'],'Monday goes only to confirmed, subscribed, current-consent attendees booked before it was due');
$s->queued=[];$s->reminders(ts('2030-09-19 08:01'));check($s->queued===['active:morning','late:morning'],'Morning includes later bookings');
$s->queued=[];$s->reminders(ts('2030-09-19 17:31'));check($s->queued===['active:ninety','late:ninety'],'Ninety minutes includes later bookings');
$s->queued=[];$s->reminders(ts('2030-09-09 08:01'));check($s->queued===[],'No attendee reminder on earlier Mondays');
$s->reminders(ts('2030-09-19 19:01'));check($s->queued===[],'No reminder after start');
$records->record['presented_at']='2030-09-16 19:00:00';$s->reminders(ts('2030-09-16 08:01'));check($s->queued===['active:morning'],'Monday webinar gets one morning email');
echo "PASS booked-attendee targeting, Monday/morning/90-minute triggers, late bookings, unsubscribes, stale consent, pending registrations and Monday deduplication.\n";
