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
        if (!is_string($action) || !in_array($action, ['redirect','list','getform','getjsondata','editmapping','deletemapping','edit'], true)) throw new DomainException('invalid');
        if ($action === 'redirect') return;
        $this->requireAdmin();
        foreach ($input as $value) if (!is_string($value)) throw new DomainException('invalid');
        if (!preg_match('/^[a-zA-Z0-9_-]{0,32}$/D', $input['id'] ?? '')) throw new DomainException('invalid');
        if (!in_array($action, ['editmapping','deletemapping','edit'], true)) return;
        if ($method !== 'POST') throw new DomainException('method');
        if (!$this->csrf()->consume('shorturl_edit', $input['csrf_token'] ?? '')) throw new DomainException('csrf');
        if ($action === 'edit' && !in_array($input['oper'] ?? '', ['add','edit','del'], true)) throw new DomainException('invalid');
        if ($action === 'deletemapping' || ($action === 'edit' && ($input['oper'] ?? '') === 'del')) {
            if (($input['id'] ?? '') === '') throw new DomainException('invalid');
            return;
        }
        foreach (['txtMatchUrl','txtTargetUrl'] as $key) {
            $value = $input[$key] ?? '';
            if ($value === '' || strlen($value) > 255 || preg_match('/[\x00-\x20\x7f]/', $value)) throw new DomainException('invalid');
        }
        $target = $input['txtTargetUrl'];
        if (str_starts_with($target, 'www.')) $target = 'http://'.$target;
        if (!filter_var($target, FILTER_VALIDATE_URL) || !in_array(strtolower((string)parse_url($target, PHP_URL_SCHEME)), ['http','https'], true)
            || parse_url($target, PHP_URL_USER) !== null || parse_url($target, PHP_URL_PASS) !== null) throw new DomainException('invalid');
        if (!preg_match('/^[0-9]{1,9}$/D', $input['ordering'] ?? '0') || !in_array($input['is_dynamic'] ?? '0', ['0','on'], true)) throw new DomainException('invalid');
        for ($i=1; $i<=5; $i++) foreach (['txtKeyTable','txtKeyField'] as $key) {
            if (!preg_match('/^(?:[a-zA-Z_][a-zA-Z0-9_]{0,63})?$/D', $input[$key.$i] ?? '')) throw new DomainException('invalid');
        }
    }
}
