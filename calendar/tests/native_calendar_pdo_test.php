<?php
/** Run from an installed local root. All fixture records roll back. */
if(PHP_SAPI!=='cli'||getenv('CHISIMBA_CALENDAR_TEST')!=='1')exit("Set CHISIMBA_CALENDAR_TEST=1 locally.\n");
$GLOBALS['kewl_entry_point_run']=true;$_SERVER['REQUEST_METHOD']='CLI';$_SERVER['HTTP_HOST']='chisimba.test:8445';$_SERVER['SCRIPT_NAME']='/ch/index.php';$_SERVER['QUERY_STRING']='';
require 'classes/core/engine_class_inc.php';$engine=new engine();
set_error_handler(static function($n,$s){if(error_reporting()&$n)throw new RuntimeException($s);return false;});
$events=$engine->getObject('dbcalendar','calendarbase');$prefix='qa-cal-'.bin2hex(random_bytes(5));
try{$events->transaction(function()use($engine,$events,$prefix){
    foreach(['personal'=>['0','root',$prefix],'course'=>['1',$prefix,$prefix],'site'=>['1','root',$prefix],'outsider'=>['0','root',$prefix.'-other']] as $label=>$scope){
        $events->insert(['id'=>$prefix.'-'.$label,'eventdate'=>'2026-10-14','eventtitle'=>$prefix.' '.$label,'eventdetails'=>'<p>Fixture details</p>','eventurl'=>'','userorcontext'=>$scope[0],'context'=>$scope[1],'userFirstEntry'=>$scope[2],'timefrom'=>'09:00:00','timeto'=>'10:00:00','multiday_event'=>'0','alert_state'=>'1']);
    }
    $view=$engine->newObject('calendarinterface','calendar');$view->userId=$prefix;$view->contextCode=$prefix;
    $view->setupCalendar('10','2026');$first=$view->getCalendar().$view->getEventsList();
    $view->setupCalendar('10','2026');$second=$view->getCalendar().$view->getEventsList();
    if($first!==$second || !str_contains($second,$prefix.' personal') || !str_contains($second,$prefix.' course') || !str_contains($second,$prefix.' site') || str_contains($second,$prefix.' outsider'))throw new RuntimeException('PDO selection or repeat render failed');
    $view->setupCalendar('09','2026');if($view->month!==9)throw new RuntimeException('Padded month rejected');
    $view->setupCalendar([],[]);if($view->month!==(int)date('n')||$view->year!==(int)date('Y'))throw new RuntimeException('Invalid date fallback failed');
    throw new LogicException('rollback-fixtures');
});}catch(LogicException $e){if($e->getMessage()!=='rollback-fixtures')throw $e;}
foreach(['personal','course','site','outsider'] as $label)if($events->getRow('id',$prefix.'-'.$label))throw new RuntimeException('Rollback failed');
echo "PASS PDO category selection, outsider exclusion, repeated rendering, date normalisation and rollback\n";
