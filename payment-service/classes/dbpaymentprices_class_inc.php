<?php
if (empty($GLOBALS['kewl_entry_point_run'])) { die('You cannot view this page directly'); }
class dbpaymentprices extends dbTable
{
    public function init($tableName=null,$pearDb=null,$errorCallback='globalPearErrorHandler') { parent::init('tbl_payment_service_prices',$pearDb,$errorCallback); }
    public function byVersion($productId,$version) { $rows=$this->selectPrepared('SELECT * FROM '.$this->_tableName.' '.('WHERE product_id=? AND version_code=? LIMIT 1'),[$productId,$version]); return is_array($rows)&&count($rows)?$rows[0]:null; }
    public function currentForProduct($productId,$at=null) { $at=$at?:date('Y-m-d H:i:s'); $rows=$this->selectPrepared('SELECT * FROM '.$this->_tableName.' '.('WHERE product_id=? AND effective_from<=? AND (effective_until IS NULL OR effective_until>?) ORDER BY effective_from DESC LIMIT 1'),[$productId,$at,$at]); return is_array($rows)&&count($rows)?$rows[0]:null; }
    public function forProduct($productId) { $rows=$this->selectPrepared('SELECT * FROM '.$this->_tableName.' '.('WHERE product_id=? ORDER BY effective_from DESC'),[$productId]); return is_array($rows)?$rows:array(); }

}
?>
