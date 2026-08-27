<?php
if (empty($GLOBALS['kewl_entry_point_run'])) { die('You cannot view this page directly'); }
class dbpaymentsubscriptions extends dbTable
{
    public function init($tableName=null,$pearDb=null,$errorCallback='globalPearErrorHandler'){parent::init('tbl_payment_service_subscriptions',$pearDb,$errorCallback);}
    public function byCustomerPlan($provider,$customer,$plan){$rows=$this->getAll('WHERE provider_code='.$this->quote($provider).' AND provider_customer_id='.$this->quote($customer).' AND provider_plan_id='.$this->quote($plan).' LIMIT 1');return is_array($rows)&&count($rows)?$rows[0]:null;}
    public function bySubscription($provider,$subscription){$rows=$this->getAll('WHERE provider_code='.$this->quote($provider).' AND provider_subscription_id='.$this->quote($subscription).' LIMIT 1');return is_array($rows)&&count($rows)?$rows[0]:null;}
    public function recent($limit=200){$limit=max(1,min(500,(int)$limit));$rows=$this->getAll('ORDER BY updated_at DESC LIMIT '.$limit);return is_array($rows)?$rows:array();}
    public function remember(array $values){$row=$this->byCustomerPlan($values['provider_code'],$values['provider_customer_id'],$values['provider_plan_id']);if($row){$fields=array('state'=>$values['state'],'updated_at'=>date('Y-m-d H:i:s'));if(!empty($values['provider_subscription_id']))$fields['provider_subscription_id']=$values['provider_subscription_id'];return $this->update('id',$row['id'],$fields)===false?null:array_merge($row,$fields);}$values['id']=bin2hex(random_bytes(16));$values['created_at']=$values['updated_at']=date('Y-m-d H:i:s');return $this->insert($values)===false?null:$values;}
    private function quote($value){$db=$this->objEngine->getDbObj();return method_exists($db,'quoteSmart')?$db->quoteSmart((string)$value):"'".str_replace("'","''",(string)$value)."'";}
}
?>
