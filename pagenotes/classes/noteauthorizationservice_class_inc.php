<?php
/**
 * Authorization service for scoped notes.
 *
 * @author Derek Keats
 * @package pagenotes
 */
if(empty($GLOBALS['kewl_entry_point_run']))die('You cannot view this page directly');
/** Combines ownership, scope roles and extensible explicit grants. */
class noteauthorizationservice extends controller
{
 private $rank=array('view'=>1,'edit'=>2,'manage'=>3);
 private $user;
 private $access;
 /** Initialise authorization dependencies. */
 public function init(){$this->user=$this->getObject('user','security');$this->access=$this->getObject('dbnoteaccess');}
 /** Return whether the current user may create in a scope. */
 public function canCreate($type,$id){if(!$this->user->isLoggedIn())return false;if($this->user->isAdmin())return true;if($type==='personal')return hash_equals((string)$this->user->userId(),(string)$id);if($type==='context')return $this->user->isCourseAdmin($id);return false;}
 /** Return whether the current user has the requested effective permission. */
 public function allows(array $note,$needed='view'){if(!$this->user->isLoggedIn()||!isset($this->rank[$needed]))return false;if($this->user->isAdmin())return true;$userId=(string)$this->user->userId();if(hash_equals((string)$note['ownerid'],$userId))return true;if($note['scopetype']==='context'&&$this->user->isCourseAdmin($note['scopeid']))return true;$permission=$this->access->userPermission($note['id'],$userId);if($permission&&$this->rank[$permission]>=$this->rank[$needed])return true;return $this->allowsFuturePrincipal($note,$userId,$needed);}
 /** Reserved extension seam for context-role and group grants. */
 protected function allowsFuturePrincipal(array $note,$userId,$needed){return false;}
}
?>
