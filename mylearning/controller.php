<?php
if (!$GLOBALS['kewl_entry_point_run']) { die('You cannot view this page directly'); }
class mylearning extends controller
{
    private $blockEditor;
    private $user;
    private $userContext;
    private $contextBlocks;
    public $objLanguage;
    public function init()
    {
        $this->blockEditor = $this->newObject('dashboardblockeditor', 'context')->forModule('mylearning');
        $this->user = $this->getObject('user', 'security');
        $this->objLanguage = $this->getObject('language', 'language');
        $this->userContext = $this->getObject('usercontext', 'context');
        $this->contextBlocks = $this->getObject('dbcontextblocks', 'context');
    }
    public function dispatch($action)
    {
        $blockAction = in_array($action, array(
            'renderblock', 'addblock', 'removeblock', 'moveblock'
        ), true);
        if ($blockAction) {
            $this->dispatchBlockAction($action);
            return null;
        }
        $managing = $action === 'manage' && $this->blockEditor->canEdit();
        if (!$managing && !$this->mayView()) { return 'noaccess_tpl.php'; }
        $this->setVar('managingDashboard', $managing);
        $this->setVar('learningOverview', $this->getObject('studentlearningoverview', 'context')->show());
        $this->setVar('dueItems', $this->getObject('studentdueitems', 'mylearning')->show());
        $modules=$this->getObject('modules','modulecatalogue');
        $membershipAvailable=!$managing
            &&$modules->checkIfRegistered('membership-service')
            &&$modules->checkIfRegistered('payment-service');
        $this->setVar('membershipAvailable',$membershipAvailable);
        $this->setVar('membershipTier',$membershipAvailable
            ?$this->getObject('membershipservice','membership-service')->effectiveTier($this->user->userId())
            :'free');
        $this->setVar('blockEditor', $this->blockEditor);
        $this->setVar('blockEditingRevision', $managing ? $this->contextBlocks->editingRevision('mylearning') : '');
        $this->setVar('upperBlocks', $this->contextBlocks->getContextBlocks(
            'mylearning', 'right'
        ));
        $this->setVar('lowerBlocks', $this->contextBlocks->getContextBlocks(
            'mylearning', 'left'
        ));
        $this->setVar('wideBlocks', $this->contextBlocks->getContextBlocks(
            'mylearning', 'middle'
        ));
        $this->setVar('mayEditBlocks', $managing);
        $this->setVar('availableNarrowBlocks', $managing ? $this->blockEditor->options(false) : []);
        $this->setVar('availableWideBlocks', $managing ? $this->blockEditor->options(true) : []);
        return 'main_tpl.php';
    }

    private function mayView()
    {
        if (!$this->user->isLoggedIn()) { return false; }
        return count((array) $this->userContext->getContextWhereStudent($this->user->userId())) > 0;
    }
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
