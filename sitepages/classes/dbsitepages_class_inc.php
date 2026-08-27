<?php
/** Database gateway for editable site pages. */
class dbsitepages extends dbTable
{
    public function init($tableName = null, $pearDb = null, $errorCallback = 'globalPearErrorCallback')
    {
        parent::init('tbl_sitepages', $pearDb, $errorCallback);
    }
    public function find($id)
    {
        $rows = $this->getAll("WHERE id='" . addslashes((string)$id) . "'");
        return $rows ? $rows[0] : false;
    }
    public function findBySlug($slug, $publishedOnly = false)
    {
        $where = "WHERE slug='" . addslashes((string)$slug) . "'";
        if ($publishedOnly) $where .= " AND status='published'";
        $rows = $this->getAll($where);
        return $rows ? $rows[0] : false;
    }
    public function activeRows()
    {
        return $this->getAll("WHERE status<>'archived' ORDER BY title ASC");
    }
    public function savePage(array $data, $userId, $id = '')
    {
        $now = date('Y-m-d H:i:s');
        if ($id !== '') {
            if (!$this->find($id)) return false;
            $data['modifierid'] = $userId;
            $data['datemodified'] = $now;
            $this->update('id', $id, $data);
            return $this->find($id);
        }
        $id = md5(uniqid((string)mt_rand(), true));
        $data += array('id'=>$id,'creatorid'=>$userId,'modifierid'=>$userId,'datecreated'=>$now,'datemodified'=>$now);
        $this->insert($data);
        return $this->find($id);
    }
    public function archive($id, $userId)
    {
        return $this->update('id', $id, array('status'=>'archived','modifierid'=>$userId,'datemodified'=>date('Y-m-d H:i:s')));
    }
}
?>
