<?php
/** Request boundary for CMS front-page permission administration. */
if (empty($GLOBALS['kewl_entry_point_run'])) die('You cannot view this page directly');
class permissionrequest extends ChisimbaObject
{
    public function init() {}
    public function handles($action, $type): bool
    {
        return in_array($action, ['permissionsuser','addedituserpermissions','deleteuserperm'], true)
            || ($action === 'ajaxforms' && $type === 'showuserpermaddform');
    }
    public function csrf() { return $this->getObject('nativeauthwebcomposition', 'security')->build()['csrf']; }
    public function tokenField(): string
    {
        return '<input type="hidden" name="csrf_token" value="'.htmlspecialchars($this->csrf()->issue('cms_user_permissions'), ENT_QUOTES, 'UTF-8').'">';
    }
    public function check($action, $type, array $input, string $method, bool $checkWrite = true): void
    {
        if (!$this->handles($action, $type)) throw new DomainException('invalid');
        $user = $this->getObject('user', 'security');
        if (!$user->isLoggedIn() || !$user->lookupAdmin($user->userId())) throw new DomainException('denied');
        foreach ($input as $value) if (!is_string($value)) throw new DomainException('invalid');
        foreach (['id','drp_owner'] as $key) {
            if (!preg_match('/^[a-zA-Z0-9_-]{0,32}$/D', $input[$key] ?? '')) throw new DomainException('invalid');
        }
        if (!$checkWrite || !in_array($action, ['addedituserpermissions','deleteuserperm'], true)) return;
        if ($method !== 'POST') throw new DomainException('method');
        if (!$this->csrf()->consume('cms_user_permissions', $input['csrf_token'] ?? '')) throw new DomainException('csrf');
        if ($action === 'deleteuserperm' && (($input['id'] ?? '') === '' || ($input['confirm'] ?? '') !== 'yes')) throw new DomainException('invalid');
        if ($action === 'addedituserpermissions' && (($input['drp_owner'] ?? '') === '' || !in_array($input['chk_frontpage'] ?? '', ['', 'on'], true))) throw new DomainException('invalid');
    }
}
