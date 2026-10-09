<?php
/** Date boundaries for the current/archive split. @author Derek Keats */
$GLOBALS['kewl_entry_point_run']=true;
class ChisimbaObject {}
require dirname(__DIR__).'/classes/webinarschedule_class_inc.php';
function event($title,$start,$end=null,$status='published') {return ['title'=>$title,'kind'=>'webinar','status'=>$status,'presented_at'=>$start,'payload'=>json_encode(['timezone'=>'Africa/Johannesburg','ends_at'=>$end])];}
function check($ok,$label){if(!$ok)throw new RuntimeException($label);}
$now=(new DateTimeImmutable('2026-09-17 19:30:00',new DateTimeZone('Africa/Johannesburg')))->getTimestamp();
$rows=[event('Future','2026-10-15 19:00:00','2026-10-15 20:00:00'),event('Live','2026-09-17 19:00:00','2026-09-17 20:00:00'),event('Past','2026-06-18 19:00:00','2026-06-18 20:00:00'),event('Draft','2026-11-19 19:00:00',null,'draft')];
check(array_column(webinarschedule::catalogue($rows,false,$now),'title')===['Live','Future'],'Current events nearest first, including live webinar');
check(array_column(webinarschedule::catalogue($rows,true,$now),'title')===['Past'],'Past only in archive');
check(array_column(webinarschedule::catalogue($rows,true,$now+1800),'title')===['Live','Past'],'Moves at exact end, newest first');
$legacy=event('Legacy','2026-01-01 00:00:00');check(!webinarschedule::isCurrent($legacy,$now),'Historical event without end remains available');
$bad=event('Invalid','bad','bad');$bad['payload']='{}';check(!webinarschedule::isCurrent($bad,$now),'Unknown date not promoted as upcoming');
$bad=event('Earlier end','2026-09-17 19:00:00','2026-09-17 18:00:00');check(webinarschedule::end($bad)==webinarschedule::start($bad),'Invalid end cannot precede start');
echo "PASS: current/past separation, ordering, live event, exact end boundary, draft exclusion and legacy dates.\n";
