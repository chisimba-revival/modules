<?php
if (empty($GLOBALS['kewl_entry_point_run'])) { die('You cannot view this page directly'); }
class dbpaymentproviderplans extends dbTable
{
    public function init($tableName=null,$pearDb=null,$errorCallback='globalPearErrorHandler'){parent::init('tbl_payment_service_provider_plans',$pearDb,$errorCallback);}
    public function mapping($provider,$product,$version){$rows=$this->getAll('WHERE provider_code='.$this->quote($provider).' AND product_code='.$this->quote($product).' AND price_version='.$this->quote($version).' LIMIT 1');return is_array($rows)&&count($rows)?$rows[0]:null;}
    public function remember(array $values){$existing=$this->mapping($values['provider_code'],$values['product_code'],$values['price_version']);if($existing)return $existing;$values['id']=bin2hex(random_bytes(16));$values['created_at']=$values['updated_at']=date('Y-m-d H:i:s');return $this->insert($values)===false?null:$values;}
    private function quote($value){$db=$this->objEngine->getDbObj();return method_exists($db,'quoteSmart')?$db->quoteSmart((string)$value):"'".str_replace("'","''",(string)$value)."'";}
}
?>
