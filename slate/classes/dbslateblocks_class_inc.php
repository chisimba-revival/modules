<?php
/** Public-page placements using the shared transactional ordering service. */
if (empty($GLOBALS['kewl_entry_point_run'])) die('You cannot view this page directly');
class dbslateblocks extends dbTable
{
    private ?array $snapshot = null;
    public function init($tableName = null, $pearDb = null, $errorCallback = 'globalPearErrorHandler')
    {
        parent::init('tbl_slate_pageblocks');
    }
    public function editingRows($key): array
    {
        $this->getObject('dbslatepages','slate')->requirePageKey($key);
        return $this->selectPrepared('SELECT * FROM tbl_slate_pageblocks WHERE pageid = ? ORDER BY side, position, id', [$key]);
    }
    public function editingRevision($key): string
    {
        return hash('sha256', json_encode($this->editingRows($key)));
    }
    /** Render and revision must describe the same snapshot, even during another save. */
    public function capture($key): string
    {
        $this->snapshot = $this->editingRows($key);
        return hash('sha256', json_encode($this->snapshot));
    }
    public function editPlacement($key, $revision, $action, $id, $side, $direction): string
    {
        return $this->getObject('blockplacementorder','blocks')->edit(
            $this, $key, $revision, $action, $id, $side, $direction,
            ['pageid'=>$key, 'userid'=>$this->getObject('user','security')->userId()]
        );
    }
    public function getPageBlocks($key, $side): string
    {
        $html = '';
        foreach ($this->snapshot ?? $this->editingRows($key) as $row) {
            if ($row['side'] !== $side) continue;
            $html .= '<div class="block" id="'.htmlspecialchars($row['id'], ENT_QUOTES, 'UTF-8').'">';
            if ($this->getObject('slatesecurity','slate')->allowed($row['block'], $side)) {
                $parts = explode('|', $row['block']);
                $html .= $parts[0] === 'block'
                    ? $this->getObject('blocks','blocks')->showBlock($parts[1], $parts[2], null, 20, true, false)
                    : $this->getObject('dynamicblocks','blocks')->showBlock($parts[1]);
            } else {
                $html .= htmlspecialchars($this->getObject('language','language')->languageText('mod_slate_unavailableblock','slate'), ENT_QUOTES, 'UTF-8');
            }
            $html .= '</div>';
        }
        return $html;
    }
}
