<?php
/** Page editing is an explicit site permission, separate from administration. */
if (empty($GLOBALS['kewl_entry_point_run'])) die('No direct access');
class pagepolicy extends ChisimbaObject
{
    private $user;
    private $permissions;
    public function init()
    {
        $this->user = $this->getObject('user', 'security');
        $this->permissions = $this->getObject('permissionservice', 'security');
    }
    public function canManage()
    {
        if (!$this->user->isLoggedIn()) return false;
        if ($this->user->isAdmin()) return true;
        $area = $this->permissions->areaIdForName('chisimba', 'sitepages');
        $right = $area ? $this->permissions->rightIdForArea($area, 'manage') : null;
        return $right && $this->permissions->isGranted($this->user->userId(), $right);
    }
}
