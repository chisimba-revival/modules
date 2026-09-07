<?php
if (empty($GLOBALS['kewl_entry_point_run'])) die('You cannot view this page directly');
class dbliveclasssessions extends dbTable
{
    public function init($tableName=null,$pearDb=null,$errorCallback='globalPearErrorHandler'){parent::init('tbl_liveclass_sessions',$pearDb,$errorCallback);}
    public function one($id){$row=$this->getRow('id',$id);return is_array($row)?$row:null;}
    public function createSession(array $values){return $this->insert($values);}
    public function saveSession($id,array $values){return $this->update('id',$id,$values);}
    public function forContext($contextCode){$q=$this->quote($contextCode);$rows=$this->getAll("WHERE context_code=$q AND status <> 'cancelled' ORDER BY starts_at ASC");return is_array($rows)?$rows:array();}
    public function webinars(){ $rows=$this->getAll("WHERE session_type='webinar' AND status <> 'cancelled' ORDER BY starts_at ASC");return is_array($rows)?$rows:array(); }
    private function quote($value){$db=$this->objEngine->getDbObj();return method_exists($db,'quoteSmart')?$db->quoteSmart((string)$value):"'".str_replace("'","''",(string)$value)."'";}
}
?>
