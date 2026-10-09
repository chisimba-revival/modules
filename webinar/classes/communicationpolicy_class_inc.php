<?php
/** Recheck consent and event state immediately before transport delivery. @author Derek Keats */
if(empty($GLOBALS['kewl_entry_point_run']))die('No direct access');
class communicationpolicy extends ChisimbaObject
{
    public function init(){$this->loadClass('webinarschedule','webinar');}
    public function allows(array $metadata)
    {
        $r=$this->getObject('webinarregistrations')->one($metadata['registration_id']??'');
        if(!$r)return false;
        $c=$this->getObject('audienceservice','audience')->one($r['contact_id']);
        if(!$c||(int)$c['revision']!==(int)($metadata['revision']??-1))return false;
        $record=$this->getObject('webinarstore')->one($r['webinar_id']);
        if(!$record)return false;
        if(($metadata['kind']??'')==='verify')return $r['state']==='pending'&&(int)$r['expires_at']>=time()
            &&hash_equals($r['confirm_hash'],(string)($metadata['confirm_hash']??''))&&webinarschedule::canRegister($record);
        if($c['state']!=='subscribed'||$r['state']!=='confirmed')return false;
        if(($metadata['kind']??'')==='confirmed')return webinarschedule::canRegister($record)
            &&webinarschedule::start($record)->getTimestamp()===(int)($metadata['starts_at']??0);
        $start=webinarschedule::start($record);
        return in_array($metadata['kind']??'',['monday','morning','ninety'],true)&&webinarschedule::canRegister($record)
            &&$start->getTimestamp()===(int)($metadata['starts_at']??0);
    }
}
