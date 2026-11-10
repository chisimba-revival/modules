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
    /** Missing display preferences keep legacy page titles visible. */
    public static function showTitle(array $row)
    {
        if (array_key_exists('show_title', $row)) return (bool)$row['show_title'];
        $composition = json_decode($row['composition_json'] ?? '', true);
        if (array_key_exists('show_title', (array)$composition)) return $composition['show_title'] !== false;
        // Preserve the earlier deployed preference until the next normal page save.
        return ($composition['hide_title'] ?? false) !== true;
    }
    public static function version(array $row)
    {return hash('sha256',json_encode(array_intersect_key($row,array_flip(['title','slug','status','body_html','composition_json']))));}
    public function savePage(array $data,$userId,$id='') {
        $expected=$data['_expected_version']??null;unset($data['_expected_version']);
        return $this->transaction(function()use($data,$userId,$id,$expected){
            if($id!==''&&$expected!==null){$rows=$this->selectPrepared('SELECT * FROM tbl_sitepages WHERE id=? FOR UPDATE',[$id]);if(!$rows||!hash_equals(self::version($rows[0]),$expected))throw new DomainException('conflict');}
            $row=$this->persistPage($data,$userId,$id);if(!$row)throw new RuntimeException('failed');return $row;
        });
    }
    private function persistPage(array $data, $userId, $id = '')
    {
        $now = date('Y-m-d H:i:s');
        if ($id !== '') {
            if (!$this->find($id)) return false;
            $data['modifierid'] = $userId;
            $data['datemodified'] = $now;
            if($this->update('id', $id, $data)===false)return false;
            return $this->find($id);
        }
        $id = md5(uniqid((string)mt_rand(), true));
        $data += array('id'=>$id,'creatorid'=>$userId,'modifierid'=>$userId,'datecreated'=>$now,'datemodified'=>$now);
        if($this->insert($data)===false)return false;
        return $this->find($id);
    }
    public function archive($id, $userId)
    {
        return $this->update('id', $id, array('status'=>'archived','modifierid'=>$userId,'datemodified'=>date('Y-m-d H:i:s')));
    }
}
?>
