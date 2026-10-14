<?php
/** Checked event-series persistence. All access stays behind dbTable. */
if(empty($GLOBALS['kewl_entry_point_run']))die('You cannot view this page directly');
class dbcalendarentries extends dbTable
{
    public function init($tableName=null,$pearDb=null,$errorCallback='globalPearErrorCallback'){parent::init('tbl_calendar');}
    public function batch(string $id): array {
        $row=$this->getRow('id',$id);
        if(!$row)throw new RuntimeException('calendar_denied');
        $rootId=(string)($row['multiday_event_start_id']?:$row['id']);
        $root=$this->getRow('id',$rootId);
        if(!$root || !empty($root['multiday_event_start_id']))throw new RuntimeException('calendar_denied');
        $children=$this->selectPrepared('SELECT * FROM tbl_calendar WHERE multiday_event_start_id=? ORDER BY eventdate,id',[$rootId]);
        return array_merge([$root],$children);
    }
    public function revision(array $rows): string {
        $ids=array_column($rows,'id');$placeholders=implode(',',array_fill(0,count($ids),'?'));
        $attachments=$this->selectPrepared('SELECT * FROM tbl_calendar_event_attachment WHERE event_id IN ('.$placeholders.') ORDER BY id',$ids);
        return hash('sha256',json_encode([$rows,$attachments]));
    }
    private function checkedBatch($id,$revision,callable $allowed): array {
        $rows=$this->batch($id);$root=$rows[0];
        foreach($rows as $row){
            if(!$allowed($row) || (string)$row['userorcontext']!==(string)$root['userorcontext'] || (string)$row['context']!==(string)$root['context'] || (string)$row['userfirstentry']!==(string)$root['userfirstentry'])throw new RuntimeException('calendar_denied');
        }
        if(!hash_equals($this->revision($rows),$revision))throw new RuntimeException('calendar_stale');
        return $rows;
    }
    public function saveSeries(array $values,array $dates,string $id,string $revision,string $createId,callable $allowed): string {
        return $this->withDatabaseLock('calendar-events',function()use($values,$dates,$id,$revision,$createId,$allowed){
            return $this->transactionWithSavepoint(function()use($values,$dates,$id,$revision,$createId,$allowed){
                $rows=$id!==''?$this->checkedBatch($id,$revision,$allowed):[];
                $rootId=$rows?$rows[0]['id']:$createId;
                if(!$rows && !$allowed(array_change_key_case($values,CASE_LOWER)))throw new RuntimeException('calendar_denied');
                if(!$rows && $this->getRow('id',$rootId))throw new RuntimeException('calendar_stale');
                foreach($dates as $index=>$date){
                    $fields=$values;
                    $fields['eventdate']=$date;$fields['multiday_event']=count($dates)>1?'1':'0';
                    $fields['multiday_event_start_id']=$index===0?null:$rootId;
                    if(isset($rows[$index])){
                        // Scope and original author cannot be moved by an edit form.
                        unset($fields['userorcontext'],$fields['context'],$fields['userFirstEntry'],$fields['dateFirstEntry']);
                        if($this->update('id',$rows[$index]['id'],$fields)===false)throw new RuntimeException('calendar_failed');
                    }else{
                        if($index===0)$fields['id']=$rootId;
                        if(!$this->insert($fields))throw new RuntimeException('calendar_failed');
                    }
                }
                foreach(array_slice($rows,count($dates)) as $removed){
                    // Keep attachment references on the retained series root; never delete file bytes.
                    $this->executePrepared('UPDATE tbl_calendar_event_attachment SET event_id=? WHERE event_id=?',[$rootId,$removed['id']]);
                    if(!$this->delete('id',$removed['id']))throw new RuntimeException('calendar_failed');
                }
                return $rootId;
            });
        });
    }
    public function deleteSeries(string $id,string $revision,callable $allowed): void {
        $this->withDatabaseLock('calendar-events',function()use($id,$revision,$allowed){
            $this->transactionWithSavepoint(function()use($id,$revision,$allowed){
                foreach($this->checkedBatch($id,$revision,$allowed) as $row){
                    $this->executePrepared('DELETE FROM tbl_calendar_event_attachment WHERE event_id=?',[$row['id']]);
                    if(!$this->delete('id',$row['id']))throw new RuntimeException('calendar_failed');
                }
            });
        });
    }
}
