<?php
/** Explicit webinar time-zone and registration-window rules. @author Derek Keats */
if(empty($GLOBALS['kewl_entry_point_run']))die('No direct access');
class webinarschedule extends ChisimbaObject
{
    public function init(){}
    public static function start(array $record)
    {
        $data=json_decode($record['payload'],true);
        if(!is_array($data)||empty($data['timezone']))return null;
        try{return new DateTimeImmutable($record['presented_at'],new DateTimeZone($data['timezone']));}
        catch(Throwable $e){return null;}
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
