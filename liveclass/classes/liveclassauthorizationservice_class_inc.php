<?php
if (empty($GLOBALS['kewl_entry_point_run'])) die('You cannot view this page directly');
class liveclassauthorizationservice extends ChisimbaObject
{
    public function init(){ $this->user=$this->getObject('user','security');$this->members=$this->getObject('usercontext','context'); }
    public function canManage(array $session){return $this->user->isAdmin()||(!empty($session['context_code'])&&$this->user->isCourseAdmin($session['context_code']));}
    public function canView(array $session){if($session['session_type']==='webinar')return $this->user->isLoggedIn();$code=(string)$session['context_code'];return $code!==''&&($this->user->isAdmin()||$this->members->isContextMember($this->user->userId(),$code));}
    public function canCreate($type,$contextCode){return $type==='webinar'?$this->user->isAdmin():($contextCode!==''&&($this->user->isAdmin()||$this->user->isCourseAdmin($contextCode)));}
}
?>
