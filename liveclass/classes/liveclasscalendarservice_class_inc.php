<?php
if (empty($GLOBALS['kewl_entry_point_run'])) die('You cannot view this page directly');
class liveclasscalendarservice extends ChisimbaObject
{
    public function init(){ $this->calendar=$this->getObject('dbcalendar','calendarbase');$this->clock=$this->getObject('timeanddateservice','timeanddate-service');$this->user=$this->getObject('user','security'); }
    public function publish(array $session){
        if(($session['session_type']??'')!=='context'||empty($session['context_code']))return null;
        $start=$this->clock->inTimezone($session['starts_at']);if(!$start instanceof DateTimeImmutable)return null;$end=$start->modify('+'.max(5,(int)$session['duration_minutes']).' minutes');$now=date('Y-m-d H:i:s');
        $id=$this->calendar->insertSingle($start->format('Y-m-d'),0,null,$session['name'],$session['description'],$this->uri(array('action'=>'view','id'=>$session['id']),'liveclass'),'1',$session['context_code'],null,'1',$this->user->userId(),$this->user->userId(),$now,$now,$start->format('H:i:s'),$end->format('H:i:s'));
        if($id)$this->calendar->update('id',$id,array('moduleevent_table'=>'tbl_liveclass_sessions','moduleevent_id'=>$session['id']));return $id?:null;
    }
    public function remove($eventId){if(is_string($eventId)&&$eventId!=='')$this->calendar->deleteSingle($eventId);}
}
?>
