<?php
/** Existing post identity and storage, with quoted queries and explicit scope. */
if (empty($GLOBALS['kewl_entry_point_run'])) die('No direct access');
class publishingstore extends dbTable
{
    public function init($tableName=null,$pearDb=null,$errorCallback='globalPearErrorHandler')
    { parent::init('tbl_simpleblog_posts',$pearDb,$errorCallback); }
    public function post($id) { $row=$this->getRow('id',(string)$id); return is_array($row)?$row:null; }
    public function begin() {return $this->beginTransaction();}
    public function commit() {return $this->commitTransaction();}
    public function rollback() {return $this->rollbackTransaction();}
    public function lockPost($id) { $rows=$this->selectPrepared('SELECT * FROM tbl_simpleblog_posts WHERE id=? FOR UPDATE',[$id]);return $rows[0]??null; }
    public function listing($type,$scope,$status='posted',$page=1,$search='',$tag='',$year=0,$month=0,$owner=null,$category='',$reader=true) {
        $sql='SELECT * FROM tbl_simpleblog_posts WHERE post_type=? AND blogid=?';$params=[$type,$scope];
        if($owner!==null){$sql.=' AND userid=?';$params[]=$owner;}
        if($reader)$sql.=$this->readerFilter($params);
        if($status!=='all'){$sql.=' AND post_status=?';$params[]=$status;}
        if($search!==''){
            $guard=$reader&&!$this->getObject('user','security')->isLoggedIn()?"(required_tier_code IS NULL OR required_tier_code='') AND ":'';
            $sql.=' AND (post_title LIKE ? OR ('.$guard.'post_content LIKE ?))';array_push($params,'%'.$search.'%','%'.$search.'%');
        }
        if($tag!=='')$sql.=$this->classificationFilter($type,$scope,'tag',$tag,$params);
        if(is_string($category)&&$category!=='')$sql.=$this->classificationFilter($type,$scope,'category',$category,$params);
        if($year>=1800&&$year<=9999&&$month>=1&&$month<=12){$sql.=' AND YEAR(COALESCE(published_at,datecreated))=? AND MONTH(COALESCE(published_at,datecreated))=?';array_push($params,(int)$year,(int)$month);}
        $sql.=' ORDER BY COALESCE(published_at,datecreated) DESC,id DESC LIMIT 11 OFFSET '.((max(1,min(10000,(int)$page))-1)*10);
        return $this->selectPrepared($sql,$params);
    }
    private function classificationFilter($type,$scope,$kind,$term,array &$params) {
        $service=$this->getObject('classificationservice','classification');$v=$service::vocabulary($type,$scope,$kind);
        array_push($params,$v['id'],$term,$term);
        return " AND EXISTS (SELECT 1 FROM tbl_classification_links cl JOIN tbl_classification_terms ct ON ct.id=cl.term_id WHERE cl.module_id='simpleblog' AND cl.item_id=tbl_simpleblog_posts.id AND cl.vocabulary_id=? AND (ct.id=? OR ct.name=?))";
    }
    /** Apply membership visibility before pagination and aggregate counts. */
    private function readerFilter(array &$params,$prefix='') {
        $user=$this->getObject('user','security');if(!$user->isLoggedIn())return '';
        $memberships=$this->getObject('membershipservice','membership-service');$held=$memberships->effectiveTier($user->userId());$allowed=[];
        foreach($memberships->tiers() as $code=>$tier)if($memberships->tierIncludes($held,$code)){$allowed[]='?';$params[]=$code;}
        $column=$prefix.'required_tier_code';return ' AND ('.$column.' IS NULL OR '.$column."=''".($allowed?' OR '.$column.' IN ('.implode(',',$allowed).')':'').')';
    }
    public function visibleTerms($type,$scope,$kind) {
        $service=$this->getObject('classificationservice','classification');$v=$service::vocabulary($type,$scope,$kind);$params=[$v['id'],$type,$scope];$filter=$this->readerFilter($params,'p.');
        return $this->selectPrepared("SELECT DISTINCT ct.id,ct.name FROM tbl_classification_terms ct JOIN tbl_classification_links cl ON cl.term_id=ct.id JOIN tbl_simpleblog_posts p ON p.id=cl.item_id WHERE cl.module_id='simpleblog' AND cl.vocabulary_id=? AND p.post_status='posted' AND p.post_type=? AND p.blogid=?".$filter.' ORDER BY ct.name',$params);
    }
    public function tags($type,$scope) {$params=[$type,$scope];$filter=$this->readerFilter($params);return $this->selectPrepared("SELECT post_tags FROM tbl_simpleblog_posts WHERE post_status='posted' AND post_type=? AND blogid=?".$filter,$params);}
    public function archive($type,$scope) {$params=[$type,$scope];$filter=$this->readerFilter($params);return $this->selectPrepared("SELECT YEAR(COALESCE(published_at,datecreated)) AS year,MONTH(COALESCE(published_at,datecreated)) AS month,COUNT(*) AS total FROM tbl_simpleblog_posts WHERE post_status='posted' AND post_type=? AND blogid=?".$filter.' GROUP BY YEAR(COALESCE(published_at,datecreated)),MONTH(COALESCE(published_at,datecreated)) ORDER BY year DESC,month DESC',$params);}
    public function persist($id,array $values)
    {
        if ($id) { if ($this->update('id',$id,$values)===false) throw new RuntimeException('save_failed'); return $id; }
        $values['id']=bin2hex(random_bytes(16));
        if ($this->insert($values)===false) throw new RuntimeException('save_failed');
        return $values['id'];
    }

    public function remove($id) { return $this->delete('id',$id)!==false; }
}
