<?php
if (!$GLOBALS['kewl_entry_point_run']) { die('You cannot view this page directly'); }
class mylearning extends controller
{
    public function init()
    {
        $this->user = $this->getObject('user', 'security');
        $this->userContext = $this->getObject('usercontext', 'context');
        $this->contextBlocks = $this->getObject('dbcontextblocks', 'context');
    }
    public function dispatch($action)
    {
        if (!$this->mayView()) { return 'noaccess_tpl.php'; }
        $this->setVar('learningOverview', $this->getObject('studentlearningoverview', 'context')->show());
        $this->setVar(
            'sidebarBlocks',
            $this->contextBlocks->getContextBlocks('mylearning', 'left')
        );
        return 'main_tpl.php';
    }
    private function mayView()
    {
        if (!$this->user->isLoggedIn()) { return false; }
        if ($this->user->isAdmin()) { return true; }
        return count((array) $this->userContext->getContextWhereStudent($this->user->userId())) > 0;
    }
}
?>
