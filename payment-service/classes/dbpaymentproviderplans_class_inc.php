<?php
if (empty($GLOBALS['kewl_entry_point_run'])) { die('You cannot view this page directly'); }
class dbpaymentproviderplans extends dbTable
{
    public function init($tableName=null,$pearDb=null,$errorCallback='globalPearErrorHandler'){parent::init('tbl_payment_service_provider_plans',$pearDb,$errorCallback);}
    public function mapping($provider,$product,$version){$rows=$this->selectPrepared('SELECT * FROM '.$this->_tableName.' '.('WHERE provider_code=? AND product_code=? AND price_version=? LIMIT 1'),[$provider,$product,$version]);return is_array($rows)&&count($rows)?$rows[0]:null;}
    public function remember(array $values){$existing=$this->mapping($values['provider_code'],$values['product_code'],$values['price_version']);if($existing)return $existing;$values['id']=bin2hex(random_bytes(16));$values['created_at']=$values['updated_at']=date('Y-m-d H:i:s');return $this->insert($values)===false?null:$values;}

}
?>
