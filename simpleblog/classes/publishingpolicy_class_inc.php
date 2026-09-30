<?php
/** Explicit publishing scopes, using canonical identity and permission services. */
if (empty($GLOBALS['kewl_entry_point_run'])) die('No direct access');
class publishingpolicy extends ChisimbaObject
{
    private $user;
    private $permissions;
    private $memberships;
    public function init()
    {
        $this->user=$this->getObject('user','security');
        $this->permissions=$this->getObject('permissionservice','security');
        $this->memberships=$this->getObject('membershipservice','membership-service');
    }
    public function granted($right)
    {
        if (!$this->user->isLoggedIn()) return false;
        if ($this->user->isAdmin()) return true;
        $area=$this->permissions->areaIdForName('chisimba','simpleblog');
        $id=$area ? $this->permissions->rightIdForArea($area,$right) : null;
        return $id && $this->permissions->isGranted($this->user->userId(),$id);
    }
    public function personalPublisher()
    {
        return $this->user->isLoggedIn() && ($this->user->isAdmin()
            || $this->user->isLecturer() || $this->granted('personal_publish'));
    }
    public function validScope($type,$id)
    {
        if ($type==='site') return $id==='site';
        if ($type==='personal') return is_string($id) && $id!=='' && strlen($id)<=25;
        if ($type==='context') return is_string($id) && $id!=='' && strlen($id)<=32
            && (bool)$this->getObject('dbcontext','context')->getContextDetails($id);
        return false;
    }
    public function canCreate($type,$id)
    {
        if (!$this->user->isLoggedIn() || !$this->validScope($type,$id)) return false;
        if ($this->user->isAdmin()) return true;
        if ($type==='personal') return $id===(string)$this->user->userId() && $this->personalPublisher();
        if ($type==='site') return $this->granted('site_publish') || $this->granted('site_manage');
        return $this->user->isContextLecturer($this->user->userId(),$id) || $this->user->isCourseAdmin($id);
    }
    public function managesScope($type,$scope)
    { return $this->user->isLoggedIn() && ($this->user->isAdmin() || ($type==='site' && $this->granted('site_manage')) || ($type==='context' && $this->user->isCourseAdmin($scope))); }
    public function canEdit(array $post)
    {
        if (!$this->canCreate($post['post_type'],$post['blogid'])) return false;
        return $this->user->isAdmin() || $post['userid']===(string)$this->user->userId()
            || ($post['post_type']==='site' && $this->granted('site_manage'))
            || ($post['post_type']==='context' && $this->user->isCourseAdmin($post['blogid']));
    }
    public function canRead(array $post)
    {
        if (!$this->validScope($post['post_type'],$post['blogid'])) return false;
        if ($post['post_status']!=='posted') return false;
        $scopeAllowed=$post['post_type']!=='context' || ($this->user->isLoggedIn() && ($this->user->isAdmin()
            || $this->user->isContextLecturer($this->user->userId(),$post['blogid'])
            || $this->getObject('usercontext','context')->isContextMember($this->user->userId(),$post['blogid'])));
        if(!$scopeAllowed)return false;
        $tier=trim((string)($post['required_tier_code']??''));
        if($tier==='')return true;
        return $this->user->isLoggedIn() && $this->memberships->tierIncludes($this->user->userId()?$this->memberships->effectiveTier($this->user->userId()):null,$tier);
    }
    public function canPreview(array $post)
    {
        if (!$this->validScope($post['post_type'],$post['blogid']) || $post['post_status']!=='posted') return false;
        return $post['post_type']!=='context' || ($this->user->isLoggedIn() && ($this->user->isAdmin()
            || $this->user->isContextLecturer($this->user->userId(),$post['blogid'])
            || $this->getObject('usercontext','context')->isContextMember($this->user->userId(),$post['blogid'])));
    }
    /** Anonymous discovery is allowed only within the existing public scope. */
    public function canDiscover(array $post)
    {
        return $this->canRead($post) || (!$this->user->isLoggedIn() && $this->canPreview($post));
    }
}
