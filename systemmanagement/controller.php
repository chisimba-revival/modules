<?php
/** Administration controller for operational notices and maintenance mode. @author Derek Keats @package systemmanagement */
if (empty($GLOBALS['kewl_entry_point_run'])) die('You cannot view this page directly');
class systemmanagement extends controller
{
    private const CSRF='systemmanagement_mutation';
    private $user; private $config; private $notices; private $service; private $clock; private $csrf;
    /** Initialise the operational services. */
    public function init(){$this->user=$this->getObject('user','security');$this->config=$this->getObject('dbsysconfig','sysconfig');$this->notices=$this->getObject('dbsystemnotices');$this->service=$this->getObject('systemmanagementservice');$this->clock=$this->getObject('timeanddateservice','timeanddate-service');$this->csrf=$this->getObject('nativeauthwebcomposition','security')->build()['csrf'];$this->appendArrayVar('headerParams','<link rel="stylesheet" href="'.$this->getResourceUri('systemmanagement.css').'"/>');}
    /** Require authentication for the console but permit the public offline page. */
    public function requiresLogin($action){return (string)$action!=='offline';}
    /** Route administration mutations and the offline display. */
    public function dispatch($action){$action=(string)$action;if($action==='offline')return $this->offline();if(!$this->user->isAdmin())return 'noaccess_tpl.php';if($action==='save')return $this->save();if($action==='deletenotice')return $this->deleteNotice();return $this->index();}
    /** Render the administrator's operational console. */
    private function index($message='',$error=''){$this->setVar('systemMaintenance',$this->service->maintenance());$this->setVar('systemNotices',$this->notices->allNotices());$this->setVar('systemCsrf',$this->csrf->issue(self::CSRF));$this->setVar('systemMessage',$message);$this->setVar('systemError',$error);$this->setVar('systemService',$this->service);$this->setVar('systemClock',$this->clock);return 'dashboard_tpl.php';}
    /** Save either the maintenance plan or a new notice. */
    private function save(){if(!$this->validPost())return $this->index('','Your session expired. Please try again.');$kind=$this->param('kind');if($kind==='maintenance'){$start=$this->service->localToStorage($this->param('start'));$end=$this->service->localToStorage($this->param('end'));if(($this->param('start')!==''&&$start===null)||($this->param('end')!==''&&$end===null))return $this->index('','Enter valid maintenance dates.');if($start&&$end&&$end<=$start)return $this->index('','Maintenance must end after it starts.');foreach(array('MAINTENANCE_ENABLED'=>$this->param('enabled')==='1'?'1':'0','MAINTENANCE_MESSAGE'=>mb_substr($this->param('message'),0,2000),'MAINTENANCE_START_AT'=>$start?:'','MAINTENANCE_END_AT'=>$end?:'') as $name=>$value)$this->config->changeParam($name,'systemmanagement',$value);return $this->index('Maintenance plan saved.');}if($kind==='notice'){$title=mb_substr($this->param('title'),0,255);$body=mb_substr($this->param('notice_message'),0,10000);$audience=$this->param('audience');$start=$this->service->localToStorage($this->param('notice_start'));$end=$this->service->localToStorage($this->param('notice_end'));if($title===''||$body===''||!in_array($audience,array('everyone','admins','lecturers','students'),true))return $this->index('','A title, message and valid audience are required.');if($start&&$end&&$end<=$start)return $this->index('','A notice must end after it starts.');$now=$this->clock->nowStorage();$this->notices->createNotice(array('id'=>bin2hex(random_bytes(16)),'title'=>$title,'message'=>$body,'audience'=>$audience,'starts_at'=>$start,'ends_at'=>$end,'created_by'=>$this->user->userId(),'datecreated'=>$now,'datemodified'=>$now));return $this->index('Notice scheduled.');}return $this->index('','Unknown operation.');}
    /** Delete one scheduled notice. */
    private function deleteNotice(){if(!$this->validPost())return $this->index('','Your session expired.');$id=$this->param('id');if(preg_match('/^[a-f0-9]{32}$/',$id))$this->notices->removeNotice($id);return $this->index('Notice removed.');}
    /** Render the deliberately minimal public maintenance page. */
    private function offline(){$maintenance=$this->service->maintenance();if(!$maintenance['active']||$this->user->isAdmin()){header('Location: '.$this->uri(array(),'_default'));exit;}$this->setLayoutTemplate(null);$this->setVar('systemMaintenance',$maintenance);return 'offline_tpl.php';}
    /** Validate one state-changing request. */
    private function validPost(){return strtoupper((string)($_SERVER['REQUEST_METHOD']??''))==='POST'&&$this->csrf->consume(self::CSRF,$this->param('csrf_token'));}
    /** Read one trimmed scalar request value. */
    private function param($name){$value=$this->getParam($name,'');return is_scalar($value)?trim((string)$value):'';}
}
?>
