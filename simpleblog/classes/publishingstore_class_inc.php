<?php
/** Existing post identity and storage, with quoted queries and explicit scope. */
if (empty($GLOBALS['kewl_entry_point_run'])) die('No direct access');
class publishingstore extends dbTable
{
    public function init($tableName=null,$pearDb=null,$errorCallback='globalPearErrorHandler')
    { parent::init('tbl_simpleblog_posts',$pearDb,$errorCallback); }
    public function post($id) { $row=$this->getRow('id',(string)$id); return is_array($row)?$row:null; }
    public function begin() { $this->query('START TRANSACTION'); }
    public function commit() { $this->query('COMMIT'); }
    public function rollback() { $this->query('ROLLBACK'); }
    public function lockPost($id) { $rows=$this->getArray('SELECT * FROM tbl_simpleblog_posts WHERE id='.$this->quoteValue($id).' FOR UPDATE');return $rows[0]??null; }
    public function listing($type,$scope,$status='posted',$page=1,$search='',$tag='',$year=0,$month=0,$owner=null)
    {
        $sql='SELECT * FROM tbl_simpleblog_posts WHERE post_type='.$this->quoteValue($type)
            .' AND blogid='.$this->quoteValue($scope);
        if ($owner!==null) $sql.=' AND userid='.$this->quoteValue($owner);
        if ($status!=='all') $sql.=' AND post_status='.$this->quoteValue($status);
        if ($search!=='') $sql.=' AND (post_title LIKE '.$this->quoteValue('%'.$search.'%')
            .' OR post_content LIKE '.$this->quoteValue('%'.$search.'%').')';
        if ($tag!=='') $sql.=' AND post_tags LIKE '.$this->quoteValue('%'.$tag.'%');
        if ($year>=1800 && $year<=9999 && $month>=1 && $month<=12) $sql.=' AND YEAR(datecreated)='.(int)$year.' AND MONTH(datecreated)='.(int)$month;
        $sql.=' ORDER BY datecreated DESC, id DESC';
        return $this->getArrayWithLimit($sql,(max(1,min(10000,(int)$page))-1)*10,11) ?: array();
    }
    public function tags($type,$scope)
    { return $this->getArray('SELECT post_tags FROM tbl_simpleblog_posts WHERE post_status=\'posted\' AND post_type='.$this->quoteValue($type).' AND blogid='.$this->quoteValue($scope)) ?: array(); }
    public function archive($type,$scope)
    { return $this->getArray('SELECT YEAR(datecreated) AS year, MONTH(datecreated) AS month, COUNT(*) AS total FROM tbl_simpleblog_posts WHERE post_status=\'posted\' AND post_type='.$this->quoteValue($type).' AND blogid='.$this->quoteValue($scope).' GROUP BY YEAR(datecreated),MONTH(datecreated) ORDER BY year DESC,month DESC') ?: array(); }
    public function persist($id,array $values)
    {
        if ($id) { if ($this->update('id',$id,$values)===false) throw new RuntimeException('save_failed'); return $id; }
        $values['id']=bin2hex(random_bytes(16));
        if ($this->insert($values)===false) throw new RuntimeException('save_failed');
        return $values['id'];
    }
    private function quoteValue($value)
    {
        $db=$this->objEngine->getDbObj();
        if (method_exists($db,'quoteSmart')) return $db->quoteSmart((string)$value);
        if (method_exists($db,'quote')) return $db->quote((string)$value);
        throw new RuntimeException('Unsupported database quoting service');
    }
    public function remove($id) { return $this->delete('id',$id)!==false; }
}
