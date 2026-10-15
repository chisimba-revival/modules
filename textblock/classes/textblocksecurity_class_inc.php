<?php
/** Request boundary for the administrator-owned Textblock editor. */
if (empty($GLOBALS['kewl_entry_point_run'])) die('You cannot view this page directly');
class textblocksecurity extends ChisimbaObject
{
    public function init() {}
    public function csrf() { return $this->getObject('nativeauthwebcomposition', 'security')->build()['csrf']; }
    public function requireAdmin(): void
    {
        $user = $this->getObject('user', 'security');
        if (!$user->isLoggedIn() || !$user->lookupAdmin($user->userId())) throw new DomainException('editor_denied');
    }
    public function check($action, array $input, string $method): void
    {
        $this->requireAdmin();
        if (!in_array($action, ['save', 'deleteajax'], true)) throw new DomainException('editor_invalid');
        if ($method !== 'POST') throw new DomainException('editor_method');
        if (!is_string($input['csrf_token'] ?? null) || !$this->csrf()->consume('textblock_edit', $input['csrf_token'])) throw new DomainException('editor_csrf');
        foreach ($input as $value) if (!is_string($value)) throw new DomainException('editor_invalid');
        $id = $input['id'] ?? '';
        if ($action === 'deleteajax' || ($input['mode'] ?? '') === 'edit') {
            if ($id === '' || strlen($id) > 32 || !$this->getObject('dbtextblock', 'textblock')->getBlockById($id)) throw new DomainException('editor_missing');
        }
        if ($action === 'deleteajax') return;
        if (!in_array($input['mode'] ?? '', ['add', 'edit'], true)) throw new DomainException('editor_invalid');
        if (($input['mode'] === 'add' && $id !== '')
            || !preg_match('/^(?:text|widetext)(?:[1-9]|[1-5][0-9]|60)$/D', $input['blockid'] ?? '')
            || trim($input['title'] ?? '') === '' || mb_strlen($input['title'] ?? '') > 250
            || strlen($input['blocktext'] ?? '') > 250000
            || !preg_match('/^(?:[a-zA-Z_][a-zA-Z0-9_-]*)?$/D', $input['css_id'] ?? '')
            || !preg_match('/^(?:[a-zA-Z_][a-zA-Z0-9_-]*(?: +[a-zA-Z_][a-zA-Z0-9_-]*)*)?$/D', $input['css_class'] ?? '')
            || strlen($input['css_id'] ?? '') > 250 || strlen($input['css_class'] ?? '') > 250
            || !in_array($input['show_title'] ?? '', ['', 'on'], true)) throw new DomainException('editor_invalid');
    }
}
