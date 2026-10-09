<?php
/** Staff-only grouping; public catalogue selection remains unchanged. */
if(empty($GLOBALS['kewl_entry_point_run']))die('No direct access');
class webinarmanagement extends ChisimbaObject
{
 public function init(){$this->loadClass('webinarschedule','webinar');}
 public static function groups(array $records,$now=null){
  $now=$now??time();$groups=['upcoming'=>[],'recent'=>[],'older'=>[],'unscheduled'=>[]];
  foreach($records as $record){
   $start=webinarschedule::start($record);$end=webinarschedule::end($record);
   if(!$start||!$end)$group='unscheduled';
   elseif($end->getTimestamp()>$now)$group='upcoming';
   else{$cutoff=(new DateTimeImmutable('@'.$now))->setTimezone($end->getTimezone())->modify('-6 months')->getTimestamp();$group=$end->getTimestamp()>=$cutoff?'recent':'older';}
   $groups[$group][]=$record;
  }
  foreach($groups as $group=>&$rows)usort($rows,static function($a,$b)use($group){
   $x=webinarschedule::start($a);$y=webinarschedule::start($b);$order=($x?$x->getTimestamp():0)<=>($y?$y->getTimestamp():0);
   return ($group==='upcoming'?$order:-$order)?:strcmp($a['title'],$b['title'])?:strcmp($a['id'],$b['id']);
  });unset($rows);return $groups;
 }
 public static function status(array $record,$now=null){
  $data=json_decode($record['payload'],true)?:[];
  if(!empty($data['cancelled']))return 'cancelled';
  $end=webinarschedule::end($record);
  return $record['status']==='published'?($end&&$end->getTimestamp()<=($now??time())?'completed':'editor_published'):'editor_draft';
 }
}
