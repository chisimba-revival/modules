<?php
/** Runtime policy service for maintenance and audience notices. @author Derek Keats @package systemmanagement */
if (empty($GLOBALS['kewl_entry_point_run'])) die('You cannot view this page directly');
class systemmanagementservice extends ChisimbaObject
{
    private $config; private $clock; private $user; private $notices;
    /** Load canonical configuration, identity, time and notice services. */
    public function init(){$this->config=$this->getObject('dbsysconfig','sysconfig');$this->clock=$this->getObject('timeanddateservice','timeanddate-service');$this->user=$this->getObject('user','security');$this->notices=$this->getObject('dbsystemnotices');}
    /** Return the current maintenance configuration and effective state. */
    public function maintenance(){ $start=$this->value('MAINTENANCE_START_AT');$end=$this->value('MAINTENANCE_END_AT');$now=$this->clock->nowStorage();$active=$this->value('MAINTENANCE_ENABLED')==='1'&&($start===''||$start<=$now)&&($end===''||$end>$now);return array('enabled'=>$this->value('MAINTENANCE_ENABLED')==='1','active'=>$active,'message'=>$this->value('MAINTENANCE_MESSAGE'),'start'=>$start,'end'=>$end); }
    /** Decide whether this request must be sent to the offline surface. */
    public function shouldBlock($module){return $this->maintenance()['active']&&!$this->user->isAdmin()&&!in_array((string)$module,array('security','systemmanagement'),true);}
    /** Return active notices applicable to the signed-in user's broad role. */
    public function activeNotices(){if(!$this->user->isLoggedIn())return array();$audiences=array('everyone',$this->user->isAdmin()?'admins':($this->isLecturer()?'lecturers':'students'));return array_values(array_filter($this->notices->activeAt($this->clock->nowStorage()),fn($row)=>in_array($row['audience'],$audiences,true)));}
    /** Convert one local form value into canonical UTC storage. */
    public function localToStorage($value){if(trim((string)$value)==='')return null;$instant=$this->clock->parseLocal(str_replace('T',' ',trim((string)$value)),$this->clock->siteTimezone(),'Y-m-d H:i');return $instant?$this->clock->toStorage($instant):null;}
    /** Convert canonical UTC storage into a datetime-local form value. */
    public function storageToLocal($value){if(trim((string)$value)==='')return '';$instant=$this->clock->inTimezone($value);return $instant?$instant->format('Y-m-d\\TH:i'):'';}
    /** Determine whether the user lectures in at least one course. */
    private function isLecturer(){return $this->notices->userLectures($this->user->userId());}
    /** Read one module-owned configuration value. */
    private function value($name){return trim((string)$this->config->getValue($name,'systemmanagement',''));}
}
?>
