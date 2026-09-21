<?php
/** Legacy URL compatibility. Saved storage and ownership belong to Question Generator. */
if (empty($GLOBALS['kewl_entry_point_run'])) { die('No direct access'); }
class mcqgenerator extends controller
{
    public function requiresLogin($action = null) { return true; }
    public function dispatch($action = null)
    {
        $action = $this->getParam('action', 'exams');
        $params = [];
        foreach (['id', 'examid', 'setid', 'page', 'format', 'answers', 'question_type'] as $key) {
            $value = $this->getParam($key);
            if (is_string($value)) { $params[$key] = $value; }
        }
        $readActions = ['list', 'new', 'view', 'download', 'exams', 'examnew', 'examview', 'examdownload'];
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET' || !in_array($action, $readActions, true)) {
            $action = !empty($params['id']) ? (is_string($action) && str_starts_with($action, 'exam') ? 'examview' : 'view') : 'exams';
            $params['reopen'] = '1';
        }
        return $this->nextAction($action, $params, 'questiongenerator');
    }
}
