<?php
if (empty($GLOBALS['kewl_entry_point_run'])) die('You cannot view this page directly');
class dbkanbansubtasks extends dbTable
{
    public function init($tableName=null,$pearDb=null,$errorCallback='globalPearErrorHandler'){parent::init('tbl_kanban_subtasks',$pearDb,$errorCallback);}
    public function one($id){$row=$this->getRow('id',(string)$id);return is_array($row)?$row:false;}
    public function forTask($id) { return $this->selectPrepared('SELECT * FROM tbl_kanban_subtasks WHERE taskid=? ORDER BY sortorder,datecreated', [$id]); }
    public function createSubtask($taskId,$title,$sort){$now=date('Y-m-d H:i:s');$id=bin2hex(random_bytes(16));return $this->insert(array('id'=>$id,'taskid'=>$taskId,'title'=>$title,'iscompleted'=>0,'sortorder'=>$sort,'datecreated'=>$now,'datemodified'=>$now))===false?false:$id;}
    public function saveSubtask($id,array $data){$data['datemodified']=date('Y-m-d H:i:s');return $this->update('id',$id,$data)!==false;}
    /** Lock and compare the previous title so concurrent editors cannot overwrite it. */
    public function renameSubtask($id,$expectedTitle,$title)
    {
        try { return $this->transactionWithSavepoint(function () use ($id,$expectedTitle,$title) {
            $rows=$this->selectPrepared('SELECT title FROM tbl_kanban_subtasks WHERE id=? FOR UPDATE', [$id]);
            if (!isset($rows[0]) || (string)$rows[0]['title']!==$expectedTitle) return 'conflict';
            if (!$this->saveSubtask($id,['title'=>$title])) throw new RuntimeException('Subtask save failed');
            return 'saved';
        }); } catch (Throwable $error) { return 'error'; }
    }
    public function removeSubtask($id){return $this->delete('id',$id)!==false;}
    public function removeForTask($id) { $this->executePrepared('DELETE FROM tbl_kanban_subtasks WHERE taskid=?', [$id]); return true; }

}
?>
