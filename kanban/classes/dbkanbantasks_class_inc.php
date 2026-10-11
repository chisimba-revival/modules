<?php
if (empty($GLOBALS['kewl_entry_point_run'])) die('You cannot view this page directly');
class dbkanbantasks extends dbTable
{
    public function init($tableName=null,$pearDb=null,$errorCallback='globalPearErrorHandler'){parent::init('tbl_kanban_tasks',$pearDb,$errorCallback);}
    public function one($id){$row=$this->getRow('id',(string)$id);return is_array($row)?$row:false;}
    public function forBoard($id) { return $this->selectPrepared('SELECT * FROM tbl_kanban_tasks WHERE boardid=? ORDER BY status,sortorder,datecreated', [$id]); }
    public function createTask(array $data){$now=date('Y-m-d H:i:s');return $this->insert(array_merge($data,array('id'=>bin2hex(random_bytes(16)),'datecreated'=>$now,'datemodified'=>$now)));}
    public function saveTask($id,array $data){$data['datemodified']=date('Y-m-d H:i:s');return $this->update('id',$id,$data)!==false;}
    public function removeTask($id){return $this->delete('id',$id)!==false;}
    public function removeForBoard($id) { $this->executePrepared('DELETE FROM tbl_kanban_tasks WHERE boardid=?', [$id]); return true; }

}
?>
