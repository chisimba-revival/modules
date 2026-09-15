<?php
/** Explicit webinar time-zone and registration-window rules. @author Derek Keats */
if(empty($GLOBALS['kewl_entry_point_run']))die('No direct access');
class webinarschedule extends ChisimbaObject
{
    public function init(){}
    public static function start(array $record)
    {
        $data=json_decode($record['payload'],true);
        if(!is_array($data)||empty($data['timezone'])||empty($record['presented_at']))return null;
        try{return new DateTimeImmutable($record['presented_at'],new DateTimeZone($data['timezone']));}
        catch(Throwable $e){return null;}
    }
    /** Keep a live webinar in the current list until its scheduled end. */
    public static function end(array $record)
    {
        $start=self::start($record);if(!$start)return null;
        $data=json_decode($record['payload'],true);
        if(empty($data['ends_at']))return $start;
        try {
            $end=new DateTimeImmutable($data['ends_at'],$start->getTimezone());
            return $end >= $start ? $end : $start;
        } catch(Throwable $e) { return $start; }
    }
    public static function isCurrent(array $record,$now=null)
    {
        $end=self::end($record);
        return $end && $end->getTimestamp()>($now??time());
    }
    /** Deterministic catalogue selection, independently testable at date boundaries. */
    public static function catalogue(array $records,$archive=false,$now=null)
    {
        $now=$now??time();
        $rows=array_values(array_filter($records,static fn($record)=>
            $record['kind']==='webinar' && $record['status']==='published'
            && self::isCurrent($record,$now)!==$archive));
        usort($rows,static function($a,$b) use($archive){
            $x=self::start($a);$y=self::start($b);
            $order=($x?$x->getTimestamp():0)<=>($y?$y->getTimestamp():0);
            return ($archive ? -$order : $order) ?: strcmp($a['title'],$b['title']);
        });
        return $rows;
    }

    public static function canRegister(array $record,$now=null)
    {
        $data=json_decode($record['payload'],true);$start=self::start($record);
        return $record['kind']==='webinar'&&$record['status']==='published'&&$start
            && !empty($data['registration_open'])&&empty($data['cancelled'])&&$start->getTimestamp()>($now??time());
    }
    public static function reminderDue(array $record,$kind)
    {
        $start=self::start($record);if(!$start)return null;
        if($kind==='morning')return $start->setTime(8,0)->getTimestamp();
        if($kind==='ninety')return $start->getTimestamp()-5400;
        return null;
    }
}
