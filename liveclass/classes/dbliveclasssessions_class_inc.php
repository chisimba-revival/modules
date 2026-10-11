<?php
if (empty($GLOBALS['kewl_entry_point_run'])) die('You cannot view this page directly');
class dbliveclasssessions extends dbTable
{
    public function init($tableName=null,$pearDb=null,$errorCallback='globalPearErrorHandler'){parent::init('tbl_liveclass_sessions',$pearDb,$errorCallback);}
    public function one($id){$row=$this->getRow('id',$id);return is_array($row)?$row:null;}
    public function createSession(array $values){return $this->insert($values);}
    public function saveSession($id,array $values){return $this->update('id',$id,$values);}
    public function forContext($contextCode) { return $this->selectPrepared("SELECT * FROM tbl_liveclass_sessions WHERE context_code=? AND status <> 'cancelled' ORDER BY starts_at ASC",[$contextCode]); }
    public function webinars(){ $rows=$this->getAll("WHERE session_type='webinar' AND status <> 'cancelled' ORDER BY starts_at ASC");return is_array($rows)?$rows:array(); }

}
?>
