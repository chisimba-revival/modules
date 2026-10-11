<?php
if (empty($GLOBALS['kewl_entry_point_run'])) { die('You cannot view this page directly'); }
class dbpayments extends dbTable
{
    public function init($tableName = null, $pearDb = null, $errorCallback = 'globalPearErrorHandler') { parent::init('tbl_payment_service_payments', $pearDb, $errorCallback); }
    public function record(array $values) {
        $existing=$this->selectPrepared('SELECT * FROM '.$this->_tableName.' '.('WHERE provider_code=? AND provider_payment_id=? LIMIT 1'),[$values['provider_code'],$values['provider_payment_id']]);
        if (is_array($existing) && count($existing)) { return $this->update('id',$existing[0]['id'],array('state'=>$values['state'],'updated_at'=>date('Y-m-d H:i:s'))); }
        $values['id']=bin2hex(random_bytes(16)); $values['created_at']=$values['updated_at']=date('Y-m-d H:i:s'); return $this->insert($values);
    }
    public function recent($limit=200) { $limit=max(1,min(500,(int)$limit)); $rows=$this->getAll('ORDER BY updated_at DESC LIMIT '.$limit); return is_array($rows)?$rows:array(); }
    public function successfulReferenceForIntent($intentId) { $rows=$this->selectPrepared('SELECT * FROM '.$this->_tableName.' '.('WHERE intent_id=?'." AND state='succeeded' ORDER BY created_at ASC LIMIT 1"),[$intentId]); return is_array($rows)&&count($rows)?$rows[0]['provider_payment_id']:null; }

}
?>
