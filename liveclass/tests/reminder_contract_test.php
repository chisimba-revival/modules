<?php
$root=dirname(__DIR__);$service=file_get_contents($root.'/classes/liveclassreminderservice_class_inc.php');$controller=file_get_contents($root.'/controller.php');$runner=file_get_contents($root.'/../../framework/app/core_modules/communications/scripts/run_outbox_worker.php');$register=file_get_contents($root.'/register.conf');
$checks=array(
'two proven reminder intervals'=>str_contains($controller,'1440')&&str_contains($controller,'60'),
'recipients resolved when reminder becomes due'=>str_contains($service,'getContextStudents')&&str_contains($service,'function run'),
'one private idempotent email per recipient'=>str_contains($service,"'idempotencyKey'=>'liveclass:'")&&str_contains($service,'queueEmail'),
'cancelled sessions cancel pending reminders'=>str_contains($controller,"if(\$status==='cancelled')")&&str_contains($service,'function cancel'),
'mail worker expands reminders before delivery'=>strpos($runner,'liveclassreminderservice')<strpos($runner,"getObject('communicationworker'"),
'role and context terminology remain abstract'=>str_contains($register,'[-readonlys-]')&&str_contains($register,'[-context-]'));
$failed=array_keys(array_filter($checks,fn($ok)=>!$ok));foreach($checks as $name=>$ok)echo($ok?'PASS ':'FAIL ').$name.PHP_EOL;exit($failed?1:0);
