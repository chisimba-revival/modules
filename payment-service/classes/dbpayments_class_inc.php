<?php
if (empty($GLOBALS['kewl_entry_point_run'])) { die('You cannot view this page directly'); }
class dbpayments extends dbTable
{
    public function init($tableName = null, $pearDb = null, $errorCallback = 'globalPearErrorHandler') { parent::init('tbl_payment_service_payments', $pearDb, $errorCallback); }
    public function record(array $values) {
        $existing=$this->getAll('WHERE provider_code='.$this->quote($values['provider_code']).' AND provider_payment_id='.$this->quote($values['provider_payment_id']).' LIMIT 1');
        if (is_array($existing) && count($existing)) { return $this->update(array('state'=>$values['state'],'updated_at'=>date('Y-m-d H:i:s')),'id = '.$this->quote($existing[0]['id'])); }
        $values['id']=bin2hex(random_bytes(16)); $values['created_at']=$values['updated_at']=date('Y-m-d H:i:s'); return $this->insert($values);
    }
    private function quote($value) { $db=$this->objEngine->getDbObj(); return method_exists($db,'quoteSmart')?$db->quoteSmart((string)$value):"'".str_replace("'","''",(string)$value)."'"; }
}
?>
