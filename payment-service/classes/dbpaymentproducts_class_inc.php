<?php
if (empty($GLOBALS['kewl_entry_point_run'])) { die('You cannot view this page directly'); }
class dbpaymentproducts extends dbTable
{
    public function init($tableName=null,$pearDb=null,$errorCallback='globalPearErrorHandler') { parent::init('tbl_payment_service_products',$pearDb,$errorCallback); }
    public function byCode($code) { $row=$this->getRow('code',$code); return is_array($row)?$row:null; }
    public function byId($id) { $row=$this->getRow('id',$id); return is_array($row)?$row:null; }
    public function byPurpose($type,$purposeId) { $rows=$this->getAll("WHERE purpose_type='".addslashes($type)."' AND purpose_id='".addslashes($purposeId)."' ORDER BY active DESC, created_at ASC LIMIT 1"); return is_array($rows)&&$rows?$rows[0]:null; }
    public function allProducts() { $rows=$this->getAll('ORDER BY active DESC, name ASC'); return is_array($rows)?$rows:array(); }
    public function activeProducts() { $rows=$this->getAll('WHERE active=1 ORDER BY name ASC'); return is_array($rows)?$rows:array(); }
    public function searchProducts(array $filters,$offset,$limit) {
        $where=$this->searchWhere($filters);
        $offset=max(0,(int)$offset); $limit=max(1,(int)$limit);
        $rows=$this->getArray('SELECT * FROM tbl_payment_service_products'.$where.' ORDER BY active DESC, name ASC, code ASC LIMIT '.$offset.', '.$limit);
        return is_array($rows)?$rows:array();
    }
    public function countProducts(array $filters) {
        $rows=$this->getArray('SELECT COUNT(*) AS product_count FROM tbl_payment_service_products'.$this->searchWhere($filters));
        return is_array($rows)&&isset($rows[0]['product_count'])?(int)$rows[0]['product_count']:0;
    }
    private function searchWhere(array $filters) {
        $clauses=array(); $query=trim((string)($filters['query']??''));
        if($query!=='') {$like=addslashes('%'.$query.'%');$clauses[]="(name LIKE '".$like."' OR code LIKE '".$like."' OR purpose_id LIKE '".$like."')";}
        $purpose=(string)($filters['purpose']??'');
        if(in_array($purpose,array('membership','private_course'),true)) $clauses[]="purpose_type='".addslashes($purpose)."'";
        $purposeId=trim((string)($filters['purpose_id']??''));
        if($purposeId!=='') $clauses[]="purpose_id='".addslashes($purposeId)."'";
        $status=(string)($filters['status']??'');
        if($status==='active') $clauses[]='active=1'; elseif($status==='inactive') $clauses[]='active=0';
        return $clauses?' WHERE '.implode(' AND ',$clauses):'';
    }
}
?>
