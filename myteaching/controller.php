<?php
if(empty($GLOBALS['kewl_entry_point_run']))die('You cannot view this page directly');
class myteaching extends controller
{
    private $blockEditor;
    public $objLanguage;
    private $user;
    private $userContext;
    private $contextBlocks;
    public function init() {
        $this->blockEditor = $this->newObject('dashboardblockeditor', 'context')->forModule('myteaching');
        $this->user=$this->getObject('user','security');
        $this->userContext=$this->getObject('usercontext','context');
        $this->contextBlocks=$this->getObject('dbcontextblocks','context');
        $this->objLanguage=$this->getObject('language','language');
    }

    public function dispatch($action) {
        $blockAction=in_array($action,array('renderblock','addblock','removeblock','moveblock'),true);
        if($blockAction) {
            $this->dispatchBlockAction($action);
            return null;
        }
        $managing=$action==='manage'&&$this->blockEditor->canEdit();
        if(!$managing&&!$this->mayView())return 'noaccess_tpl.php';
        $this->setVar('managingDashboard',$managing);
        $this->setVar('teachingOverview',$this->getObject('teachingcourseoverview')->show());
        $this->setVar('blockEditor', $this->blockEditor);

        $this->setVar('blockEditingRevision', $managing ? $this->contextBlocks->editingRevision('myteaching') : '');

        foreach(array('right'=>'upperBlocks','left'=>'lowerBlocks','middle'=>'wideBlocks') as $side=>$variable)$this->setVar($variable,$this->contextBlocks->getContextBlocks('myteaching',$side));
        $this->setVar('mayEditBlocks',$managing);
        $this->setVar('availableNarrowBlocks',$managing ? $this->blockEditor->options(false) : []);
        $this->setVar('availableWideBlocks',$managing ? $this->blockEditor->options(true) : []);
        return 'main_tpl.php';
    }

    private function mayView(){return $this->user->isLoggedIn()&&count((array)$this->userContext->getContextWhereLecturer($this->user->userId()))>0;}

    /** Shared boundary enforces current administrator, POST, CSRF and fixed scope. */
    private function dispatchBlockAction($action)
    {
        $input = [];
        foreach (['contextcode','blockid','side','direction','revision','csrf_token'] as $key) {
            $input[$key] = $this->getParam($key);
        }
        $result = $this->blockEditor->handle($action, $input, $_SERVER['REQUEST_METHOD'] ?? '');
        http_response_code($result['status']);
        header('Content-Type: application/json; charset=UTF-8');
        header('Cache-Control: no-store');
        echo json_encode($result);
        exit;
    }
}
?>
