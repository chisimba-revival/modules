<?php
if (empty($GLOBALS['kewl_entry_point_run'])) { die('You cannot view this page directly'); }
class dbpaymentevents extends dbTable
{
    public function init($tableName = null, $pearDb = null, $errorCallback = 'globalPearErrorHandler') { parent::init('tbl_payment_service_events', $pearDb, $errorCallback); }
    public function claim(array $values) {
        $existing=$this->selectPrepared('SELECT * FROM '.$this->_tableName.' '.('WHERE provider_code=? AND provider_event_id=? LIMIT 1'),[$values['provider_code'],$values['provider_event_id']]);
        if (is_array($existing) && count($existing)) { return array('ok'=>TRUE,'duplicate'=>TRUE,'id'=>$existing[0]['id']); }
        $values['id']=bin2hex(random_bytes(16)); $values['received_at']=date('Y-m-d H:i:s');
        return $this->insert($values)===FALSE ? array('ok'=>FALSE) : array('ok'=>TRUE,'duplicate'=>FALSE,'id'=>$values['id']);
    }
    public function complete($id,$result) { return $this->update('id',$id,array('processed_at'=>date('Y-m-d H:i:s'),'processing_result'=>$result)); }
    public function recent($limit=300) { $limit=max(1,min(500,(int)$limit)); $rows=$this->getAll('ORDER BY received_at DESC LIMIT '.$limit); return is_array($rows)?$rows:array(); }

}
?>
