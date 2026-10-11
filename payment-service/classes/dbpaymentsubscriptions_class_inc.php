<?php
if (empty($GLOBALS['kewl_entry_point_run'])) { die('You cannot view this page directly'); }
class dbpaymentsubscriptions extends dbTable
{
    public function init($tableName=null,$pearDb=null,$errorCallback='globalPearErrorHandler'){parent::init('tbl_payment_service_subscriptions',$pearDb,$errorCallback);}
    public function byCustomerPlan($provider,$customer,$plan){$rows=$this->selectPrepared('SELECT * FROM '.$this->_tableName.' '.('WHERE provider_code=? AND provider_customer_id=? AND provider_plan_id=? LIMIT 1'),[$provider,$customer,$plan]);return is_array($rows)&&count($rows)?$rows[0]:null;}
    public function bySubscription($provider,$subscription){$rows=$this->selectPrepared('SELECT * FROM '.$this->_tableName.' '.('WHERE provider_code=? AND provider_subscription_id=? LIMIT 1'),[$provider,$subscription]);return is_array($rows)&&count($rows)?$rows[0]:null;}
    public function recent($limit=200){$limit=max(1,min(500,(int)$limit));$rows=$this->getAll('ORDER BY updated_at DESC LIMIT '.$limit);return is_array($rows)?$rows:array();}
    public function remember(array $values){$row=$this->byCustomerPlan($values['provider_code'],$values['provider_customer_id'],$values['provider_plan_id']);if($row){$fields=array('state'=>$values['state'],'updated_at'=>date('Y-m-d H:i:s'));if(!empty($values['provider_subscription_id']))$fields['provider_subscription_id']=$values['provider_subscription_id'];return $this->update('id',$row['id'],$fields)===false?null:array_merge($row,$fields);}$values['id']=bin2hex(random_bytes(16));$values['created_at']=$values['updated_at']=date('Y-m-d H:i:s');return $this->insert($values)===false?null:$values;}

}
?>
