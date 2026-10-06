<?php
if (empty($GLOBALS['kewl_entry_point_run'])) die('You cannot view this page directly');
class dbkanbansubtasks extends dbTable
{
    public function init($tableName=null,$pearDb=null,$errorCallback='globalPearErrorHandler'){parent::init('tbl_kanban_subtasks',$pearDb,$errorCallback);}
    public function one($id){$row=$this->getRow('id',(string)$id);return is_array($row)?$row:false;}
    public function forTask($id){$rows=$this->getAll("WHERE taskid=".$this->q($id).' ORDER BY sortorder,datecreated');return is_array($rows)?$rows:array();}
    public function createSubtask($taskId,$title,$sort){$now=date('Y-m-d H:i:s');$id=bin2hex(random_bytes(16));return $this->insert(array('id'=>$id,'taskid'=>$taskId,'title'=>$title,'iscompleted'=>0,'sortorder'=>$sort,'datecreated'=>$now,'datemodified'=>$now))===false?false:$id;}
    public function saveSubtask($id,array $data){$data['datemodified']=date('Y-m-d H:i:s');return $this->update('id',$id,$data)!==false;}
    /** Lock and compare the previous title so concurrent editors cannot overwrite it. */
    public function renameSubtask($id,$expectedTitle,$title){
        if($this->query('START TRANSACTION')===false)return 'error';
        try{
            $rows=$this->getArray('SELECT title FROM tbl_kanban_subtasks WHERE id='.$this->q($id).' FOR UPDATE');
            if(!is_array($rows))throw new RuntimeException('Subtask read failed');
            if(!isset($rows[0])||(string)$rows[0]['title']!==$expectedTitle){$this->query('ROLLBACK');return 'conflict';}
            if(!$this->saveSubtask($id,array('title'=>$title)))throw new RuntimeException('Subtask save failed');
            if($this->query('COMMIT')===false)throw new RuntimeException('Subtask commit failed');
            return 'saved';
        }catch(Throwable $error){$this->query('ROLLBACK');return 'error';}
    }
    public function removeSubtask($id){return $this->delete('id',$id)!==false;}
    public function removeForTask($taskId){return $this->query('DELETE FROM tbl_kanban_subtasks WHERE taskid='.$this->q($taskId))!==false;}
    private function q($v){$db=$this->objEngine->getDbObj();return method_exists($db,'quoteSmart')?$db->quoteSmart((string)$v):"'".str_replace("'","''",(string)$v)."'";}
}
?>
