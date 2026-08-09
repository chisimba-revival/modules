<?php
if (empty($GLOBALS['kewl_entry_point_run'])) { die('Direct access denied'); }

class faq26 extends controller
{
    protected $service;
    protected $contextService;

    public function init()
    {
        $this->service = $this->getObject('faq26service', 'faq26');
        $this->contextService = $this->getObject('dbcontext', 'context');
    }

    public function requiresLogin($action)
    {
        return true; // Admin management interface requires login
    }

    public function dispatch($action)
    {
        switch ((string)$action) {
            case 'add': return $this->add();
            case 'save': return $this->save();
            case 'delete': return $this->delete();
            case 'home':
            case '':
            default:
                return $this->home();
        }
    }

    protected function resolveScope(): array
    {
        $contextCode = $this->contextService->getContextCode();
        if (!empty($contextCode)) {
            return array('type' => 'context', 'id' => $contextCode);
        }
        $userId = $this->getParam('user_id');
        if (!empty($userId)) {
            return array('type' => 'user', 'id' => 'user_' . $userId);
        }
        return array('type' => 'global', 'id' => 'global');
    }

    protected function home()
    {
        $scope = $this->resolveScope();
        $faqs = $this->service->getFaqsForScope($scope['type'], $scope['id'], true);

        $this->setVar('scope_type', $scope['type']);
        $this->setVar('scope_id', $scope['id']);
        $this->setVar('faqs', $faqs);
        $this->setVar('addUrl', $this->uri(array('action' => 'add'), 'faq26'));
        $this->setVar('deleteUrl', $this->uri(array('action' => 'delete'), 'faq26'));

        return 'home_tpl.php';
    }

    protected function add()
    {
        $scope = $this->resolveScope();
        $this->setVar('scope_type', $scope['type']);
        $this->setVar('scope_id', $scope['id']);
        $this->setVar('saveUrl', $this->uri(array('action' => 'save'), 'faq26'));
        $this->setVar('cancelUrl', $this->uri(array('action' => 'home'), 'faq26'));
        return 'edit_tpl.php';
    }

    protected function save()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return $this->nextAction('home', array(), 'faq26');
        }
        $user = $this->getObject('user', 'security');
        $input = array(
            'question'   => $this->getParam('question'),
            'answer'     => $this->getParam('answer'),
            'scope_type' => $this->getParam('scope_type', 'global'),
            'scope_id'   => $this->getParam('scope_id', 'global'),
            'creator_id' => $user->userId()
        );

        $result = $this->service->saveFaq($input);
        return $this->nextAction('home', array('msg' => $result['ok'] ? 'saved' : 'error'), 'faq26');
    }

    protected function delete()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return $this->nextAction('home', array(), 'faq26');
        }
        $id = $this->getParam('id');
        $scope = $this->resolveScope();
        $this->service->deleteFaq($id, $scope['type'], $scope['id']);
        return $this->nextAction('home', array('msg' => 'deleted'), 'faq26');
    }
}
?>
