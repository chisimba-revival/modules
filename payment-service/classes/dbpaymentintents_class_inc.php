<?php
if (empty($GLOBALS['kewl_entry_point_run'])) { die('You cannot view this page directly'); }
class dbpaymentintents extends dbTable
{
    public function init($tableName = null, $pearDb = null, $errorCallback = 'globalPearErrorHandler') { parent::init('tbl_payment_service_intents', $pearDb, $errorCallback); }
    public function byId($id) { $row=$this->getRow('id',$id); return is_array($row)?$row:null; }
    public function byIdempotency($key) { $row=$this->getRow('idempotency_key',$key); return is_array($row)?$row:null; }
    public function create(array $values) { return $this->insert($values); }
    public function recent($limit=200) { $limit=max(1,min(500,(int)$limit)); $rows=$this->getAll('ORDER BY updated_at DESC LIMIT '.$limit); return is_array($rows)?$rows:array(); }
    public function transition($id, $from, array $fields) { $row=$this->byId($id); return !$row||$row['state']!==$from?false:$this->update('id',$id,$fields); }
    private function quote($value) { $db=$this->objEngine->getDbObj(); return method_exists($db,'quoteSmart')?$db->quoteSmart((string)$value):"'".str_replace("'","''",(string)$value)."'"; }
}
?>
