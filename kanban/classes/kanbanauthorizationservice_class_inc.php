<?php
if (empty($GLOBALS['kewl_entry_point_run'])) die('You cannot view this page directly');
class kanbanauthorizationservice extends object
{
    private $rank=array('view'=>1,'edit'=>2,'manage'=>3);
    public function init(){ $this->user=$this->getObject('user','security');$this->groups=$this->getObject('managegroups','contextgroups');$this->access=$this->getObject('dbkanbanaccess'); }
    public function canCreate($scopeType,$scopeId){if(!$this->user->isLoggedIn())return false;if($this->user->isAdmin())return true;if($scopeType==='personal')return hash_equals((string)$this->user->userId(),(string)$scopeId);if($scopeType==='context')return $this->user->isCourseAdmin($scopeId);return false;}
    public function allows(array $board,$needed='view'){
        if(!$this->user->isLoggedIn()||!isset($this->rank[$needed]))return false;
        if($this->user->isAdmin())return true;
        $userId=(string)$this->user->userId();
        if(hash_equals((string)$board['ownerid'],$userId))return true;
        if($board['scopetype']==='context'&&$this->user->isCourseAdmin($board['scopeid']))return true;
        $permission=$this->eligibleDirectUser($board,$userId)?$this->access->userPermission($board['id'],$userId):false;
        if($permission&&$this->rank[$permission]>=$this->rank[$needed])return true;
        return $this->allowsFuturePrincipal($board,$userId,$needed);
    }
    protected function allowsFuturePrincipal(array $board,$userId,$needed){
        // Extension seam: resolve context_role and group principals here when groupwork is enabled.
        return false;
    }
    public function eligibleDirectUser(array $board,$userId){
        if($this->user->inAdminGroup($userId))return true;
        if($board['scopetype']==='context')return $this->user->isContextLecturer($userId,$board['scopeid']);
        foreach((array)$this->groups->usercontextcodes($userId) as $context){$code=is_array($context)?($context['contextcode']??$context['contextCode']??''):(string)$context;if($code!==''&&$this->user->isContextLecturer($userId,$code))return true;}
        return false;
    }
}
?>
