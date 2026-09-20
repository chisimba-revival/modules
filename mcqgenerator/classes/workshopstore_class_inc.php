<?php
/** Persist private authoring sets through the canonical database connection. @author Derek Keats */
if (empty($GLOBALS['kewl_entry_point_run'])) die('No direct access');
class workshopstore extends dbTable
{
    public function init($tableName=null,$pearDb=null,$errorCallback='globalPearErrorHandler')
    { parent::init('tbl_questionworkshop_sets',$pearDb,$errorCallback); $this->checked($this->objEngine->getDbObj()->setCharset('utf8mb4')); }
    public function one($id) { $rows=$this->rows('SELECT * FROM tbl_questionworkshop_sets WHERE id='.$this->q($id)); return $rows[0]??null; }
    public function owned($owner,$page=1,$examid='')
    { return $this->rows('SELECT * FROM tbl_questionworkshop_sets WHERE ownerid='.$this->q($owner).' AND examid='.$this->q($examid).' ORDER BY datemodified DESC,id DESC LIMIT '.((max(1,min(10000,(int)$page))-1)*20).',21') ?: []; }
    /** Lightweight chapter picker in natural title order; do not load chapter source text. */
    public function examChapters($owner,$page=1,$examid='')
    {
        $rows=$this->rows("SELECT id,title,reviewed,question_type FROM tbl_questionworkshop_sets WHERE ownerid=".$this->q($owner)." AND examid=".$this->q($examid)." AND state='generated' AND questions_json<>'[]'");
        usort($rows,static function($a,$b){
            $normal=static fn($title)=>preg_replace('/[-_\s]+/u',' ',$title);
            return strnatcasecmp($normal($a['title']),$normal($b['title']))?:strcmp($a['id'],$b['id']);
        });
        return array_slice($rows,(max(1,min(10000,(int)$page))-1)*20,21);
    }
    public function createSet($owner,$title,$source,array $questions,$count=5,$examid='',$type='mcq')
    {
        if(!in_array($type,['mcq','short_answer'],true))throw new DomainException('questions_invalid');
        $this->beginTransaction();
        try {
            // Lock the parent against deletion while adding a chapter.
            $parent=$this->rows('SELECT id FROM tbl_mcqgenerator_exams WHERE id='.$this->q($examid).' AND ownerid='.$this->q($owner).' FOR UPDATE');
            if(!$parent)throw new DomainException('not_found');
            $now=$this->getObject('timeanddateservice','timeanddate-service')->nowStorage();
            $id=$this->insertSet(['id'=>bin2hex(random_bytes(16)),'ownerid'=>$owner,'examid'=>$examid,'question_type'=>$type,'title'=>$title,'source_text'=>$source,'validation_json'=>'[]','questions_json'=>json_encode($questions,JSON_THROW_ON_ERROR),'imported_testid'=>'','imported_context'=>'','question_count'=>$count,'state'=>'ready','version'=>1,'reviewed'=>0,'datecreated'=>$now,'datemodified'=>$now]);
            if (!$id) throw new RuntimeException('storage_failed');
            $this->commitTransaction();return $id;
        }catch(Throwable $e){$this->rollbackTransaction();throw $e;}
    }
    public function saveSet(array $row,$title,array $questions,$reviewed)
    {
        $this->beginTransaction();
        try {
            $rows=$this->rows('SELECT version,state FROM tbl_questionworkshop_sets WHERE id='.$this->q($row['id']).' FOR UPDATE');
            if (!$rows || in_array($rows[0]['state'],['generating','processing'],true) || (int)$rows[0]['version']!==(int)$row['version']) throw new DomainException('changed');
            if ($this->updateSet($row['id'],['title'=>$title,'questions_json'=>json_encode($questions,JSON_THROW_ON_ERROR),'reviewed'=>$reviewed?1:0,'version'=>(int)$row['version']+1,'datemodified'=>$this->getObject('timeanddateservice','timeanddate-service')->nowStorage()])===false) throw new RuntimeException('storage_failed');
            $this->commitTransaction();
        } catch(Throwable $e){$this->rollbackTransaction();throw $e;}
    }
    /** An atomic state change prevents duplicate paid requests for the same set. */
    public function claim(array $row,$count=null)
    {
        $db=$this->objEngine->getDbObj();
        $count=$count??(int)$row['question_count'];
        if(!is_int($count)||$count<1||$count>30)throw new DomainException('count_invalid');
        $result=$db->exec("UPDATE tbl_questionworkshop_sets SET question_count=".$count.",state='generating',validation_json='[]' WHERE id=".$this->q($row['id'])." AND state IN ('ready','failed') AND questions_json='[]'");
        if(is_object($result)||$result===false)throw new RuntimeException('storage_failed');
        return $result===1;
    }
    /** Claim an append job and save its original snapshot in the same atomic write. */
    public function claimMore(array $row,array $job)
    {
        return $this->run("UPDATE tbl_questionworkshop_sets SET state='generating',version=version+1,generation_json=".$this->q(json_encode($job,JSON_THROW_ON_ERROR))." WHERE id=".$this->q($row['id'])." AND ownerid=".$this->q($row['ownerid'])." AND version=".(int)$row['version']." AND state='generated'")===1;
    }
    public function finish(array $row,array $questions,array $issues=[],$requestedCount=null,$reviewed=false)
    {
        if($this->updateSet($row['id'],['questions_json'=>json_encode($questions,JSON_THROW_ON_ERROR),'validation_json'=>json_encode($issues,JSON_THROW_ON_ERROR),'generation_json'=>'[]','state'=>'generated','question_count'=>$requestedCount??(int)$row['question_count'],'reviewed'=>$reviewed?1:0,'version'=>(int)$row['version']+1])===false)throw new RuntimeException('storage_failed');
    }
    public function failed(array $row,array $issues=[]) { $this->updateSet($row['id'],['state'=>'failed','validation_json'=>json_encode($issues,JSON_THROW_ON_ERROR)]); }
    public function saveJob(array $row,array $job,$state='generating'){
        $this->updateSet($row['id'],['generation_json'=>json_encode($job,JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE),'state'=>$state]);
    }
    public function claimPart(array $row){
        return $this->run("UPDATE tbl_questionworkshop_sets SET state='processing' WHERE id=".$this->q($row['id'])." AND state='generating'")===1;
    }
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
