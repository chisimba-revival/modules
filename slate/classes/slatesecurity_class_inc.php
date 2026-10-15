<?php
/** Administrator, origin and scope checks for legacy Slate requests. */
if (empty($GLOBALS['kewl_entry_point_run'])) die('You cannot view this page directly');
class slatesecurity extends ChisimbaObject
{
    public function init() {}
    public function csrf() { return $this->getObject('nativeauthwebcomposition', 'security')->build()['csrf']; }
    public function check($action, array $input, string $method): void
    {
        if (!in_array($action, ['view','manage','edit','pagesave','delete','gettaken','renderblock','addblock','removeblock','moveblock'], true)) throw new DomainException('editor_invalid');
        if ($action === 'view') {
            if (!is_string($input['page'] ?? '') || (($input['page'] ?? '') !== '' && !preg_match('/^[1-9][0-9]{0,8}$/D', $input['page']))) throw new DomainException('editor_invalid');
            return;
        }
        $user = $this->getObject('user','security');
        if (!$user->isLoggedIn() || !$user->lookupAdmin($user->userId())) throw new DomainException('editor_denied');
        if (in_array($action, ['manage','edit','gettaken'], true)) {
            foreach (['id','mode'] as $key) if (!is_string($input[$key] ?? '')) throw new DomainException('editor_invalid');
            if (strlen($input['id'] ?? '') > 32 || !in_array($input['mode'] ?? '', ['', 'add', 'edit'], true)) throw new DomainException('editor_invalid');
            if ($action === 'edit' && ($input['mode'] ?? '') === 'edit' && !$this->getObject('dbslatepages','slate')->getPageById($input['id'])) throw new DomainException('editor_missing');
            return;
        }
        if ($method !== 'POST') throw new DomainException('editor_method');
        if (!is_string($input['csrf_token'] ?? null) || !$this->csrf()->consume('slate_edit', $input['csrf_token'])) throw new DomainException('editor_csrf');
        foreach ($input as $value) if (!is_string($value)) throw new DomainException('editor_invalid');
        $repo = $this->getObject('dbslatepages','slate');
        if (in_array($action, ['pagesave','delete'], true)) {
            $id = $input['id'] ?? '';
            $row = null;
            if ($action === 'delete' || ($input['mode'] ?? '') === 'edit') {
                if ($id === '' || strlen($id) > 32) throw new DomainException('editor_missing');
                $row = $repo->getPageById($id)[0] ?? null;
                if (!$row) throw new DomainException('editor_missing');
            }
            if ($action === 'delete') return;
            if (!in_array($input['mode'] ?? '', ['add','edit'], true)
                || ($input['mode'] === 'add' && $id !== '')
                || !preg_match('/^[1-9][0-9]{0,8}$/D', $input['page'] ?? '')
                || trim($input['title'] ?? '') === '' || mb_strlen($input['title'] ?? '') > 250
                || trim($input['description'] ?? '') === '' || strlen($input['description'] ?? '') > 10000
                || ($row && (string)$row['page'] !== $input['page'])) throw new DomainException('editor_invalid');
            return;
        }
        $pageId = $input['pageId'] ?? '';
        if (!preg_match('/^[a-f0-9]{32}$/D', $pageId)) throw new DomainException('editor_invalid');
        $repo->requirePageKey($pageId);
        $block = $input['blockid'] ?? '';
        if (in_array($action, ['removeblock','moveblock'], true)) {
            $rows = $repo->selectPrepared('SELECT id FROM tbl_slate_pageblocks WHERE id = ? AND pageid = ?', [$block, $pageId]);
            if (!$rows) throw new DomainException('editor_missing');
            if ($action === 'moveblock' && !in_array($input['direction'] ?? '', ['up','down'], true)) throw new DomainException('editor_invalid');
            return;
        }
        $side = $input['side'] ?? '';
        if (!in_array($side, ['left','middle','right'], true)) throw new DomainException('editor_invalid');
        if (!$this->allowed($block, $side)) throw new DomainException('editor_denied');
    }

    public function allowed(string $block, string $side): bool
    {
        // Public Slate pages may contain site-scoped blocks, never a visitor's private blocks.
        $dynamic = $this->getObject('dynamicblocks','blocks');
        foreach ($side === 'middle' ? $dynamic->getWideSiteBlocks() : $dynamic->getSmallSiteBlocks() as $row) {
            if ($block === 'dynamicblock|'.$row['id'].'|'.$row['module'] && $this->getObject('modules','modulecatalogue')->checkIfRegistered($row['module'])) return true;
        }
        if ($this->getObject('modules','modulecatalogue')->checkIfRegistered('contentblocks')) {
            foreach ((array)$this->getObject('dbcontentblocks','contentblocks')->getBlocksArr($side === 'middle' ? 'content_widetext' : 'content_text') as $row) {
                if ($block === 'block|'.$row['id'].'|contentblocks') return true;
            }
        }
        foreach ($this->getObject('dbmoduleblocks','modulecatalogue')->getBlocks($side === 'middle' ? 'wide' : 'normal', 'site') as $row) {
            if ($row['moduleid'] !== 'contentblocks' && $block === 'block|'.$row['blockname'].'|'.$row['moduleid']
                && $this->getObject('modules','modulecatalogue')->checkIfRegistered($row['moduleid'])) return true;
        }
        return false;
    }
}
