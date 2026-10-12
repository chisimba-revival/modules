<?php
if (empty($GLOBALS['kewl_entry_point_run'])) die('You cannot view this page directly');
class dbkanbantasks extends dbTable
{
    public function init($tableName=null,$pearDb=null,$errorCallback='globalPearErrorHandler'){parent::init('tbl_kanban_tasks',$pearDb,$errorCallback);}
    public function one($id){$row=$this->getRow('id',(string)$id);return is_array($row)?$row:false;}
    public function forBoard($id) { return $this->selectPrepared('SELECT * FROM tbl_kanban_tasks WHERE boardid=? ORDER BY status,CASE WHEN sortorder<0 THEN sortorder ELSE 0 END,datecreated,id', [$id]); }
    /** Old positive timestamps are not reliable ranks. Negative values hold explicit order. */
    public function createTask(array $data){
        try { return $this->transactionWithSavepoint(function () use ($data) {
            $this->lockBoard($data['boardid']);
            $rows=array_values(array_filter($this->forBoard($data['boardid']),fn($row)=>$row['status']===$data['status']));
            $now=date('Y-m-d H:i:s');
            $id=bin2hex(random_bytes(16));
            if($this->insert(array_merge($data,array('id'=>$id,'sortorder'=>0,'datecreated'=>$now,'datemodified'=>$now)))===false)throw new RuntimeException('Task insertion failed');
            $rows[]=array('id'=>$id);
            $this->rankTasks($rows);
            return $id;
        }); } catch (Throwable $error) { return false; }
    }
    /** Serialise ordering within a board, including simultaneous task creation. */
    private function lockBoard($id){
        if(!$this->selectPrepared('SELECT id FROM tbl_kanban_boards WHERE id=? FOR UPDATE',[$id]))throw new DomainException('Board unavailable');
    }
    private function rankTasks(array $rows){
        $rank=-count($rows);
        foreach($rows as $row)$this->executePrepared('UPDATE tbl_kanban_tasks SET sortorder=? WHERE id=?',[$rank++,$row['id']]);
    }
    /** Move relative to a real task, never a browser-supplied numeric rank. */
    public function moveTask($id,$status,$beforeId='', $expectedStatus=null, $expectedOrder=null){
        if(!in_array($status,['not_started','in_progress','completed'],true))throw new DomainException('Invalid status');
        $task=$this->one($id);
        if(!$task)throw new DomainException('Task unavailable');
        return $this->transactionWithSavepoint(function () use ($task,$id,$status,$beforeId,$expectedStatus,$expectedOrder) {
            $this->lockBoard($task['boardid']);
            $current=$this->one($id);
            if(!$current||$current['boardid']!==$task['boardid']||($expectedStatus!==null&&$current['status']!==$expectedStatus))throw new DomainException('Task changed');
            $column=array_values(array_filter($this->forBoard($task['boardid']),fn($row)=>$row['status']===$status));
            if($expectedOrder!==null&&array_column($column,'id')!==$expectedOrder)throw new DomainException('Column changed');
            $rows=array_values(array_filter($column,fn($row)=>$row['id']!==$id));
            $ids=array_column($rows,'id');
            $position=$beforeId===''?count($rows):array_search($beforeId,$ids,true);
            if($position===false)throw new DomainException('Destination changed');
            array_splice($rows,$position,0,[$current]);
            if(!$this->saveTask($id,['status'=>$status]))throw new RuntimeException('Task move failed');
            $this->rankTasks($rows);
            return true;
        });
    }
    public function saveTask($id,array $data){$data['datemodified']=date('Y-m-d H:i:s');return $this->update('id',$id,$data)!==false;}
    public function removeTask($id){return $this->delete('id',$id)!==false;}
    public function removeForBoard($id) { $this->executePrepared('DELETE FROM tbl_kanban_tasks WHERE boardid=?', [$id]); return true; }

}
?>
