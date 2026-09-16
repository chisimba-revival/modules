<?php
/** Course membership never makes another student's recording public. @author Derek Keats */
if (empty($GLOBALS['kewl_entry_point_run'])) die('No direct access');
class spokenpolicy extends ChisimbaObject
{
    public function member($userId, $context)
    {
        if (!is_string($userId) || $userId==='' || strlen($userId)>25
            || !is_string($context) || $context === '' || $context === 'root') return false;
        $user = $this->getObject('user', 'security');
        $account=$this->getObject('userservice','security')->findByUserId($userId);
        if (!$account || empty($account['isactive']) || !$this->getObject('dbcontext', 'context')->getContextDetails($context)) return false;
        if ($user->lookupAdmin($userId)) return true;
        $groups=$this->getObject('groupservice','groupadmin');
        foreach (['Lecturers','Students','Guest'] as $role) {
            $id=$groups->groupIdForName($context.'^'.$role);
            if ($id && $groups->isGroupMember($userId,$id)) return true;
        }
        return false;
    }
    public function teacher($userId, $context)
    {
        if (!$this->member($userId, $context)) return false;
        $user = $this->getObject('user', 'security');
        return $user->lookupAdmin($userId) || $user->isContextLecturer($userId, $context);
    }
    public function mayRead(array $attempt, $userId, $context)
    {
        return (string)$attempt['contextcode'] === (string)$context && $this->member($userId, $context)
            && ((string)$attempt['userid'] === (string)$userId || $this->teacher($userId, $context));
    }
    public function mayChangeTranscript(array $attempt, $userId, $context)
    {
        return (string)$attempt['userid'] === (string)$userId && $this->mayRead($attempt, $userId, $context);
    }
}
