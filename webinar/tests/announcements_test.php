<?php
$GLOBALS['kewl_entry_point_run']=true;class ChisimbaObject{};
require __DIR__.'/../classes/webinarschedule_class_inc.php';require __DIR__.'/../classes/webinarannouncements_class_inc.php';
function ensure($v,$m){if(!$v)throw new RuntimeException($m);}
function event($id,$start,$extras=[]){return ['id'=>$id,'kind'=>'webinar','status'=>'published','presented_at'=>$start,'payload'=>json_encode($extras+['timezone'=>'Africa/Johannesburg','registration_open'=>true])];}
function stamp($s){return (new DateTimeImmutable($s,new DateTimeZone('Africa/Johannesburg')))->getTimestamp();}
$oct=event('oct','2026-10-15 19:00');$nov=event('nov','2026-11-19 19:00');$jan=event('jan','2027-01-21 19:00');$rows=[$jan,$oct,$nov];
foreach(['2026-09-21','2026-09-28','2026-10-05','2026-10-12'] as $day){
 $p=webinarannouncements::plan($rows,stamp($day.' 08:01'),1);
 ensure(count($p)===1&&$p[0]['kind']==='weekly'&&$p[0]['ids']===['oct','nov','jan'],'Every Monday includes all upcoming events, even without an event that week');
}
ensure(webinarannouncements::plan($rows,stamp('2026-10-01 08:01'),1)===[],'No first-of-month newsletter');
ensure(webinarannouncements::plan($rows,stamp('2026-10-15 08:01'),1)===[],'No event-day newsletter to everyone');
ensure(webinarannouncements::plan($rows,stamp('2026-10-15 21:01'),1)===[],'No after-event newsletter');
ensure(webinarannouncements::plan($rows,stamp('2026-09-21 07:59'),1)===[],'Not before configured hour');
ensure(webinarannouncements::plan($rows,stamp('2026-09-21 08:01'),stamp('2026-09-21 08:00:30'))===[],'No catch-up before activation');
ensure(webinarannouncements::plan($rows,stamp('2026-09-22 08:01'),1)===[],'No stale next-day newsletter');
$cancel=event('c','2026-10-15 19:00',['cancelled'=>true]);$draft=$oct;$draft['status']='draft';
ensure(webinarannouncements::plan([$cancel,$draft],stamp('2026-09-21 08:01'),1)===[],'Empty, cancelled and private lists do not send');
$p=webinarannouncements::plan([$jan],stamp('2026-12-28 08:01'),1);ensure($p[0]['ids']===['jan'],'Year rollover');
ensure(webinarschedule::reminderDue($oct,'monday')===stamp('2026-10-12 08:00'),'Booked Monday reminder is in event week');
ensure(webinarschedule::reminderDue($oct,'morning')===stamp('2026-10-15 08:00'),'Booked event morning');
ensure(webinarschedule::reminderDue($oct,'ninety')===stamp('2026-10-15 17:30'),'Booked ninety-minute reminder');
$mon=event('m','2026-10-12 19:00');ensure(webinarschedule::reminderDue($mon,'monday')===null,'Monday morning not duplicated');
ensure(webinarschedule::reminderDue($mon,'morning')===stamp('2026-10-12 08:00'),'Monday webinar retains its morning reminder');
$dst=event('dst','2026-10-29 19:00',['timezone'=>'Europe/London']);
ensure(webinarschedule::reminderDue($dst,'monday')===(new DateTimeImmutable('2026-10-26 08:00',new DateTimeZone('Europe/London')))->getTimestamp(),'Attendee Monday observes local DST boundary');
ensure(!(new webinarannouncements)->stillCurrent(['schedule_key'=>'month:old','expires_at'=>time()+86400]),'Old automatic announcement policies cannot deliver');
echo "PASS every-Monday general newsletter; no monthly/after-event mail; booked Monday/morning/90-minute timing; grouping, activation, cancellation and DST.\n";
