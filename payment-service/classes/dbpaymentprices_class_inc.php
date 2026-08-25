<?php
if (empty($GLOBALS['kewl_entry_point_run'])) { die('You cannot view this page directly'); }
class dbpaymentprices extends dbTable
{
    public function init($tableName=null,$pearDb=null,$errorCallback='globalPearErrorHandler') { parent::init('tbl_payment_service_prices',$pearDb,$errorCallback); }
    public function byVersion($productId,$version) { $rows=$this->getAll('WHERE product_id='.$this->quote($productId).' AND version_code='.$this->quote($version).' LIMIT 1'); return is_array($rows)&&count($rows)?$rows[0]:null; }
    public function currentForProduct($productId,$at=null) { $at=$at?:date('Y-m-d H:i:s'); $rows=$this->getAll('WHERE product_id='.$this->quote($productId).' AND effective_from<='.$this->quote($at).' AND (effective_until IS NULL OR effective_until>'.$this->quote($at).') ORDER BY effective_from DESC LIMIT 1'); return is_array($rows)&&count($rows)?$rows[0]:null; }
    public function forProduct($productId) { $rows=$this->getAll('WHERE product_id='.$this->quote($productId).' ORDER BY effective_from DESC'); return is_array($rows)?$rows:array(); }
    private function quote($value) { $db=$this->objEngine->getDbObj(); return method_exists($db,'quoteSmart')?$db->quoteSmart((string)$value):"'".str_replace("'","''",(string)$value)."'"; }
}
?>
