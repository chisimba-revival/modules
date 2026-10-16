<?php
/** Request boundary for legacy ShortURL administration during native migration. */
if (empty($GLOBALS['kewl_entry_point_run'])) die('You cannot view this page directly');
class shorturlsecurity extends ChisimbaObject
{
    public function init() {}
    public function csrf() { return $this->getObject('nativeauthwebcomposition', 'security')->build()['csrf']; }
    public function requireAdmin(): void
    {
        $user = $this->getObject('user', 'security');
        if (!$user->isLoggedIn() || !$user->lookupAdmin($user->userId())) throw new DomainException('denied');
    }
    public function tokenField(): string
    {
        return '<input type="hidden" name="csrf_token" value="'.htmlspecialchars($this->csrf()->issue('shorturl_edit'), ENT_QUOTES, 'UTF-8').'">';
    }
    public function check($action, array $input, string $method): void
    {
        if (!is_string($action) || !in_array($action,['redirect','list','edit','save','delete'],true)) throw new DomainException('invalid');
        if ($action==='redirect') return;
        $this->requireAdmin();
        foreach ($input as $value) if (!is_string($value)) throw new DomainException('invalid');
        if (!preg_match('/^[a-zA-Z0-9_-]{0,32}$/D',$input['id']??'')) throw new DomainException('invalid');
        if (!in_array($action,['save','delete'],true)) return;
        if ($method!=='POST') throw new DomainException('method');
        if (!$this->csrf()->consume('shorturl_edit',$input['csrf_token']??'')) throw new DomainException('csrf');
        if ($action==='delete' && (($input['id']??'')==='' || ($input['confirm']??'')!=='yes')) throw new DomainException('invalid');
        if (($input['id']??'')!=='' && !preg_match('/^[a-f0-9]{64}$/D',$input['revision']??'')) throw new DomainException('stale');
    }
}
