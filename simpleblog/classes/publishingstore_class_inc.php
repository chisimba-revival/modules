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
    public function listing($type,$scope,$status='posted',$page=1,$search='',$tag='',$year=0,$month=0,$owner=null,$category='')
    {
        $sql='SELECT * FROM tbl_simpleblog_posts WHERE post_type='.$this->quoteValue($type)
            .' AND blogid='.$this->quoteValue($scope);
        if ($owner!==null) $sql.=' AND userid='.$this->quoteValue($owner);
        if ($status!=='all') $sql.=' AND post_status='.$this->quoteValue($status);
        if ($search!=='') $sql.=' AND (post_title LIKE '.$this->quoteValue('%'.$search.'%')
            .' OR post_content LIKE '.$this->quoteValue('%'.$search.'%').')';
        if ($tag!=='') $sql.=$this->classificationFilter($type,$scope,'tag',$tag);
        if (is_string($category) && $category!=='') $sql.=$this->classificationFilter($type,$scope,'category',$category);
        if ($year>=1800 && $year<=9999 && $month>=1 && $month<=12) $sql.=' AND YEAR(COALESCE(published_at,datecreated))='.(int)$year.' AND MONTH(COALESCE(published_at,datecreated))='.(int)$month;
        $sql.=' ORDER BY COALESCE(published_at,datecreated) DESC, id DESC';
        return $this->getArrayWithLimit($sql,(max(1,min(10000,(int)$page))-1)*10,11) ?: array();
    }
    private function classificationFilter($type,$scope,$kind,$term)
    {
        $service=$this->getObject('classificationservice','classification');
        $v=$service::vocabulary($type,$scope,$kind);
        return ' AND EXISTS (SELECT 1 FROM tbl_classification_links cl JOIN tbl_classification_terms ct ON ct.id=cl.term_id WHERE cl.module_id=\'simpleblog\' AND cl.item_id=tbl_simpleblog_posts.id AND cl.vocabulary_id='.$this->quoteValue($v['id']).' AND (ct.id='.$this->quoteValue($term).' OR ct.name='.$this->quoteValue($term).'))';
    }
    public function visibleTerms($type,$scope,$kind)
    {
        $service=$this->getObject('classificationservice','classification');$v=$service::vocabulary($type,$scope,$kind);
        return $this->getArray('SELECT DISTINCT ct.id,ct.name FROM tbl_classification_terms ct JOIN tbl_classification_links cl ON cl.term_id=ct.id JOIN tbl_simpleblog_posts p ON p.id=cl.item_id WHERE cl.module_id=\'simpleblog\' AND cl.vocabulary_id='.$this->quoteValue($v['id']).' AND p.post_status=\'posted\' AND p.post_type='.$this->quoteValue($type).' AND p.blogid='.$this->quoteValue($scope).' ORDER BY ct.name') ?: [];
    }
    public function tags($type,$scope)
    { return $this->getArray('SELECT post_tags FROM tbl_simpleblog_posts WHERE post_status=\'posted\' AND post_type='.$this->quoteValue($type).' AND blogid='.$this->quoteValue($scope)) ?: array(); }
    public function archive($type,$scope)
    { return $this->getArray('SELECT YEAR(COALESCE(published_at,datecreated)) AS year, MONTH(COALESCE(published_at,datecreated)) AS month, COUNT(*) AS total FROM tbl_simpleblog_posts WHERE post_status=\'posted\' AND post_type='.$this->quoteValue($type).' AND blogid='.$this->quoteValue($scope).' GROUP BY YEAR(COALESCE(published_at,datecreated)),MONTH(COALESCE(published_at,datecreated)) ORDER BY year DESC,month DESC') ?: array(); }
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
