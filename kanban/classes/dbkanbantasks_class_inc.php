<?php
if (empty($GLOBALS['kewl_entry_point_run'])) die('You cannot view this page directly');
class dbkanbantasks extends dbTable
{
    public function init($tableName=null,$pearDb=null,$errorCallback='globalPearErrorHandler'){parent::init('tbl_kanban_tasks',$pearDb,$errorCallback);}
    public function one($id){$row=$this->getRow('id',(string)$id);return is_array($row)?$row:false;}
    public function forBoard($id){$rows=$this->getAll("WHERE boardid=".$this->q($id).' ORDER BY status,sortorder,datecreated');return is_array($rows)?$rows:array();}
    public function createTask(array $data){$now=date('Y-m-d H:i:s');return $this->insert(array_merge($data,array('id'=>bin2hex(random_bytes(16)),'datecreated'=>$now,'datemodified'=>$now)));}
    public function saveTask($id,array $data){$data['datemodified']=date('Y-m-d H:i:s');return $this->update('id',$id,$data)!==false;}
    public function removeTask($id){return $this->delete('id',$id)!==false;}
    public function removeForBoard($boardId){return $this->query('DELETE FROM tbl_kanban_tasks WHERE boardid='.$this->q($boardId))!==false;}
    private function q($v){$db=$this->objEngine->getDbObj();return method_exists($db,'quoteSmart')?$db->quoteSmart((string)$v):"'".str_replace("'","''",(string)$v)."'";}
}
?>
