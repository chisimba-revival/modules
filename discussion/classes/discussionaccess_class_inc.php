<?php
/** Current read policy shared by Discussion routes and reusable blocks. @author Derek Keats */
if (empty($GLOBALS['kewl_entry_point_run'])) die('No direct access');
class discussionaccess extends ChisimbaObject {
    public function mayRead($discussion) {
        if (!is_array($discussion)) return false;
        $context=trim((string)$this->getObject('dbcontext','context')->getContextCode()) ?: 'root';
        if (!hash_equals($context,(string)($discussion['discussion_context']??''))) return false;
        $user=$this->getObject('user','security');
        $manager=$context==='root'?$user->isAdmin():$user->isCourseAdmin($context);
        if (!$manager && ($discussion['discussion_visible']??'N')!=='Y') return false;
        if (!$user->isLoggedIn() && $this->getObject('dbsysconfig','sysconfig')->getValue('ALLOW_PRELOGIN','discussion')!=='1') return false;
        if ($context!=='root' && !$this->getObject('courseawarelaunchservice','context')->mayUseActiveCourse($user->userId(),'discussion')) return false;
        $group=$discussion['discussion_workgroup']??'';
        if ($group==='' || $group===null || $manager) return true;
        return $this->getObject('modules','modulecatalogue')->checkIfRegistered('workgroup')
            && $this->getObject('dbworkgroupusers','workgroup')->memberOfWorkGroup($user->userId(),$group);
    }
}
