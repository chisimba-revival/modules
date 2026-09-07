<?php
$root=dirname(__DIR__);$modules=dirname($root);$service=file_get_contents($root.'/classes/liveclasscalendarservice_class_inc.php');$controller=file_get_contents($root.'/controller.php');$schema=file_get_contents($root.'/sql/tbl_liveclass_sessions.sql');$schedule=file_get_contents($modules.'/mylearning/classes/studentdueitems_class_inc.php');
$checks=array(
 'context event uses session start and duration'=>strpos($service,'insertSingle')!==false&&strpos($service,'duration_minutes')!==false,
 'calendar entry links back to canonical live session'=>strpos($service,"'liveclass'")!==false&&strpos($service,"'action'=>'view'")!==false,
 'calendar linkage is retained for cancellation'=>strpos($schema,"'calendar_event_id'")!==false&&strpos($controller,"'calendar_event_id'")!==false,
 'cancellation removes the calendar event'=>strpos($controller,'calendar->remove')!==false,
 'student schedules read live sessions without duplicating ownership'=>strpos($schedule,"getObject('dbliveclasssessions', 'liveclass')")!==false&&strpos($schedule,"'provider'=>'liveclass'")!==false,
 'live schedule action opens the session page'=>strpos($schedule,"'module'=>'liveclass'")!==false&&strpos($schedule,"'action'=>'view'")!==false,
);
foreach($checks as $label=>$ok){if(!$ok){fwrite(STDERR,"FAIL: $label\n");exit(1);}echo "PASS: $label\n";}
