<?php
if (empty($GLOBALS['kewl_entry_point_run'])) die('You cannot view this page directly');
class dbkanbansubtasks extends dbTable
{
    public function init($tableName=null,$pearDb=null,$errorCallback='globalPearErrorHandler'){parent::init('tbl_kanban_subtasks',$pearDb,$errorCallback);}
    public function one($id){$row=$this->getRow('id',(string)$id);return is_array($row)?$row:false;}
    public function forTask($id){$rows=$this->getAll("WHERE taskid=".$this->q($id).' ORDER BY sortorder,datecreated');return is_array($rows)?$rows:array();}
    public function createSubtask($taskId,$title,$sort){$now=date('Y-m-d H:i:s');return $this->insert(array('id'=>bin2hex(random_bytes(16)),'taskid'=>$taskId,'title'=>$title,'iscompleted'=>0,'sortorder'=>$sort,'datecreated'=>$now,'datemodified'=>$now));}
    public function saveSubtask($id,array $data){$data['datemodified']=date('Y-m-d H:i:s');return $this->update('id',$id,$data)!==false;}
    public function removeSubtask($id){return $this->delete('id',$id)!==false;}
    public function removeForTask($taskId){return $this->query('DELETE FROM tbl_kanban_subtasks WHERE taskid='.$this->q($taskId))!==false;}
    private function q($v){$db=$this->objEngine->getDbObj();return method_exists($db,'quoteSmart')?$db->quoteSmart((string)$v):"'".str_replace("'","''",(string)$v)."'";}
}
?>
