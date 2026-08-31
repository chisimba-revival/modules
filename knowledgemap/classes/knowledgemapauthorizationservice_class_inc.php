<?php
/**
 * Authorization service for scoped and shared Active Knowledge Maps.
 *
 * @author Derek Keats
 * @package knowledgemap
 */
if (empty($GLOBALS['kewl_entry_point_run'])) die('You cannot view this page directly');

/** Combines scope membership, ownership and invited-user grants. */
class knowledgemapauthorizationservice extends controller
{
    private $rank=array('view'=>1,'edit'=>2,'manage'=>3);
    private $user;
    private $userContext;
    private $access;

    /** Initialise authorization dependencies. */
    public function init(){$this->user=$this->getObject('user','security');$this->userContext=$this->getObject('usercontext','context');$this->access=$this->getObject('dbknowledgemapaccess');}

    /** Return whether the current user may create in the supplied scope. */
    public function canCreate($scopeType,$scopeId){if(!$this->user->isLoggedIn())return false;if($this->user->isAdmin())return true;if($scopeType==='personal')return hash_equals((string)$this->user->userId(),(string)$scopeId);if($scopeType==='context')return $this->user->isCourseAdmin($scopeId);return false;}

    /** Return whether the current user has the requested effective permission. */
    public function allows(array $map,$needed='view'){
        if(!$this->user->isLoggedIn()||!isset($this->rank[$needed]))return false;
        if($this->user->isAdmin())return true;
        $userId=(string)$this->user->userId();
        if(hash_equals((string)$map['ownerid'],$userId))return true;
        if($map['scopetype']==='context'){
            if($this->user->isCourseAdmin($map['scopeid']))return true;
            if($needed==='view'&&$this->userContext->isContextMember($userId,$map['scopeid']))return true;
        }
        if($map['scopetype']==='site'&&$needed==='view')return true;
        $permission=$this->access->userPermission($map['id'],$userId);
        return $permission&&$this->rank[$permission]>=$this->rank[$needed];
    }

    /** Return whether a username resolves to an invitable Chisimba user. */
    public function invitedUserId($username){$userId=$this->user->getUserId((string)$username);return $userId?(string)$userId:false;}
}
?>
