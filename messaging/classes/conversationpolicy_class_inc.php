<?php
/** Current context access; never trust a previously selected room. @author Derek Keats */
if (empty($GLOBALS['kewl_entry_point_run'])) die('No direct access');
class conversationpolicy extends ChisimbaObject
{
    private $user; private $contexts; private $members;
    public function init() {
        $this->user=$this->getObject('user','security');
        $this->contexts=$this->getObject('dbcontext','context');
        $this->members=$this->getObject('usercontext','context');
    }
    public function choices() {
        if(!$this->user->isLoggedIn())return [];
        $rows=$this->user->isAdmin()?$this->contexts->getAll():array_map(fn($code)=>$this->contexts->getContextDetails($code),$this->members->getUserContext($this->actor()));
        return array_values(array_filter($rows,fn($row)=>is_array($row)&&$this->mayRead($row['contextcode'])));
    }
    public function actor() { return (string)$this->user->userId(); }
    public function mayRead($context) {
        return $this->user->isLoggedIn() && is_string($context) && $context!=='' && strlen($context)<=32
            && (bool)$this->contexts->getContextDetails($context)
            && ($this->user->isAdmin() || $this->members->isContextMember($this->actor(),$context)
                || $this->user->isContextLecturer($this->actor(),$context) || $this->user->isCourseAdmin($context));
    }
    public function mayModerate($context) {
        return $this->mayRead($context) && ($this->user->isAdmin()
            || $this->user->isContextLecturer($this->actor(),$context) || $this->user->isCourseAdmin($context));
    }
}
