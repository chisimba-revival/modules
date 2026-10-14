<?php
$GLOBALS['kewl_entry_point_run']=true;
set_error_handler(static function($n,$s){throw new ErrorException($s,0,$n);});
class ChisimbaObject{}
require dirname(__DIR__).'/classes/calendarinterface_class_inc.php';
function check($ok,$message){if(!$ok)throw new RuntimeException($message);}
$view=new calendarinterface();
$event=['eventtitle'=>'Birds & <img src=x onerror=alert(1)>','eventdetails'=>'<script>alert(1)</script>Visible <b>detail</b>','timefrom'=>'10:00:00','timeto'=>'11:00:00','calendar_category'=>'event_user'];
$earlier=$event;$earlier['eventtitle']='Earlier';$earlier['timefrom']='08:00:00';$earlier['calendar_category']='event_context';
$view->rawEventsList=[14=>[$event,$earlier]];
$render=new ReflectionMethod(calendarinterface::class,'getCalenderEvents');$render->invoke($view,'small');$small=$view->eventsList[14];
check(str_contains($small,'<details class="event_user">')&&str_contains($small,'<summary>Birds &amp; '),'native disclosure and escaping');
check(!str_contains($small,'<script')&&!str_contains($small,'<img')&&!str_contains($small,'onclick')&&!str_contains($small,'jQuery'),'no active injected markup or legacy handlers');
check(strpos($small,'Earlier')<strpos($small,'Birds'),'time ordering');
$render->invoke($view,'wide');$wide=$view->eventsList[14];check(str_contains($wide,'class="event_user"')&&str_contains($wide,'class="event_context"'),'grid categories preserved');
$view->rawEventsList=[];$render->invoke($view,'small');check($view->eventsList===[],'empty refresh clears stale output');
$view->rawEventsList=[14=>[$event]];$view->resetEventsArray();check($view->rawEventsList===[]&&$view->eventsList===[],'full reset clears source and rendered data');
echo "PASS native disclosure, safe text, time ordering, grid categories and repeated/empty render reset\n";
