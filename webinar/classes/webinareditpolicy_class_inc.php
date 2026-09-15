<?php
/** Site webinar management uses canonical capabilities, not translated role names. @author Derek Keats */
if(empty($GLOBALS['kewl_entry_point_run']))die('No direct access');
class webinareditpolicy extends ChisimbaObject
{
    public function init() {}
    public function canManage()
    {
        $user=$this->getObject('user','security');
        if(!$user->isLoggedIn())return false;
        if($user->isAdmin())return true;
        $permissions=$this->getObject('permissionservice','security');
        $area=$permissions->areaIdForName('chisimba','webinar');
        $right=$area?$permissions->rightIdForArea($area,'manage'):null;
        return (bool)($right&&$permissions->isGranted($user->userId(),$right));
    }
}
