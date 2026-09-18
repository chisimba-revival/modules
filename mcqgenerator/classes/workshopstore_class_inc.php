<?php
/** Persist private authoring sets through the canonical database connection. @author Derek Keats */
if (empty($GLOBALS['kewl_entry_point_run'])) die('No direct access');
class workshopstore extends dbTable
{
    public function init($tableName=null,$pearDb=null,$errorCallback='globalPearErrorHandler')
    { parent::init('tbl_questionworkshop_sets',$pearDb,$errorCallback); $this->checked($this->objEngine->getDbObj()->setCharset('utf8mb4')); }
    public function one($id) { $rows=$this->rows('SELECT * FROM tbl_questionworkshop_sets WHERE id='.$this->q($id)); return $rows[0]??null; }
    public function owned($owner,$page=1)
    { return $this->rows('SELECT * FROM tbl_questionworkshop_sets WHERE ownerid='.$this->q($owner).' ORDER BY datemodified DESC,id DESC LIMIT '.((max(1,min(10000,(int)$page))-1)*20).',21') ?: []; }
    public function createSet($owner,$title,$source,array $questions,$count=5)
    {
        $now=$this->getObject('timeanddateservice','timeanddate-service')->nowStorage();
        $id=$this->insertSet(['id'=>bin2hex(random_bytes(16)),'ownerid'=>$owner,'title'=>$title,'source_text'=>$source,'validation_json'=>'[]','questions_json'=>json_encode($questions,JSON_THROW_ON_ERROR),'imported_testid'=>'','imported_context'=>'','question_count'=>$count,'state'=>'ready','version'=>1,'reviewed'=>0,'datecreated'=>$now,'datemodified'=>$now]);
        if (!$id) throw new RuntimeException('storage_failed');
        return $id;
    }
    public function saveSet(array $row,$title,array $questions,$reviewed)
    {
        $this->beginTransaction();
        try {
            $rows=$this->rows('SELECT version FROM tbl_questionworkshop_sets WHERE id='.$this->q($row['id']).' FOR UPDATE');
            if (!$rows || (int)$rows[0]['version']!==(int)$row['version']) throw new DomainException('changed');
            if ($this->updateSet($row['id'],['title'=>$title,'questions_json'=>json_encode($questions,JSON_THROW_ON_ERROR),'reviewed'=>$reviewed?1:0,'version'=>(int)$row['version']+1,'datemodified'=>$this->getObject('timeanddateservice','timeanddate-service')->nowStorage()])===false) throw new RuntimeException('storage_failed');
            $this->commitTransaction();
        } catch(Throwable $e){$this->rollbackTransaction();throw $e;}
    }
    /** An atomic state change prevents duplicate paid requests for the same set. */
    public function claim(array $row)
    {
        $db=$this->objEngine->getDbObj();
        $result=$db->exec("UPDATE tbl_questionworkshop_sets SET state='generating',validation_json='[]' WHERE id=".$this->q($row['id'])." AND state IN ('ready','failed') AND questions_json='[]'");
        if(is_object($result)||$result===false)throw new RuntimeException('storage_failed');
        return $result===1;
    }
    public function finish(array $row,array $questions,array $issues=[])
    {
        if($this->updateSet($row['id'],['questions_json'=>json_encode($questions,JSON_THROW_ON_ERROR),'validation_json'=>json_encode($issues,JSON_THROW_ON_ERROR),'state'=>'generated','version'=>(int)$row['version']+1])===false)throw new RuntimeException('storage_failed');
    }
    public function failed(array $row,array $issues=[]) { $this->updateSet($row['id'],['state'=>'failed','validation_json'=>json_encode($issues,JSON_THROW_ON_ERROR)]); }
    /** Serialise imports with a row lock; never duplicate a saved set on retry. */
    public function importOnce($id,$context,callable $create)
    {
        $this->beginTransaction();
        try {
            $rows=$this->rows('SELECT * FROM tbl_questionworkshop_sets WHERE id='.$this->q($id).' FOR UPDATE');
            if(!$rows)throw new DomainException('not_found');
            $row=$rows[0];
            if(!empty($row['imported_testid'])){
                if($row['imported_context']!==$context)throw new DomainException('already_imported');
                $test=$row['imported_testid'];
            }else{
                $test=$create($row);
                if($this->updateSet($id,['imported_testid'=>$test,'imported_context'=>$context])===false)throw new RuntimeException('storage_failed');
            }
            $this->commitTransaction();return $test;
        }catch(Throwable $e){$this->rollbackTransaction();throw $e;}
    }
    /** Remove only the owner's selected revision; imported course copies are independent. */
    public function removeOwned($id,$owner,$version)
    {
        $affected=$this->run('DELETE FROM tbl_questionworkshop_sets WHERE id='.$this->q($id).' AND ownerid='.$this->q($owner).' AND version='.(int)$version);
        if($affected!==1)throw new DomainException('changed');
    }
    private function checked($result)
    {
        if($result===false || is_object($result)&&is_a($result,'PEAR_Error'))throw new RuntimeException('storage_failed');
        return $result;
    }
    /** Do not send private source/question values to generic developer query logs. */
    private function run($sql,$read=false)
    {
        $db=$this->objEngine->getDbObj();$db->pushErrorHandling(PEAR_ERROR_RETURN);
        try{return $this->checked($read?$db->queryAll($sql):$db->exec($sql));}
        finally{$db->popErrorHandling();}
    }
    private function rows($sql)
    {
        $rows=$this->run($sql,true);
        foreach($rows as &$row)foreach($row as &$value)if($value===null)$value='';
        return $rows;
    }
    private function insertSet(array $values)
    {
        $this->run('INSERT INTO tbl_questionworkshop_sets ('.implode(',',array_keys($values)).') VALUES ('.implode(',',array_map([$this,'q'],array_values($values))).')');
        return $values['id'];
    }
    private function updateSet($id,array $values)
    {
        $parts=[];foreach($values as $key=>$value)$parts[]=$key.'='.$this->q($value);
        return $this->run('UPDATE tbl_questionworkshop_sets SET '.implode(',',$parts).' WHERE id='.$this->q($id));
    }
    private function q($value) { return (string)$value===''?"''":$this->objEngine->getDbObj()->quote((string)$value,'text'); }
}
