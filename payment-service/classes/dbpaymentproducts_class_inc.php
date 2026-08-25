<?php
if (empty($GLOBALS['kewl_entry_point_run'])) { die('You cannot view this page directly'); }
class dbpaymentproducts extends dbTable
{
    public function init($tableName=null,$pearDb=null,$errorCallback='globalPearErrorHandler') { parent::init('tbl_payment_service_products',$pearDb,$errorCallback); }
    public function byCode($code) { $row=$this->getRow('code',$code); return is_array($row)?$row:null; }
    public function byId($id) { $row=$this->getRow('id',$id); return is_array($row)?$row:null; }
    public function allProducts() { $rows=$this->getAll('ORDER BY active DESC, name ASC'); return is_array($rows)?$rows:array(); }
    public function activeProducts() { $rows=$this->getAll('WHERE active=1 ORDER BY name ASC'); return is_array($rows)?$rows:array(); }
}
?>
