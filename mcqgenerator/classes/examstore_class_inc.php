<?php
/** Versioned private exam snapshots. @author Derek Keats */
if(empty($GLOBALS['kewl_entry_point_run']))die('No direct access');
class examstore extends dbTable
{
    public function init($tableName=null,$pearDb=null,$errorCallback='globalPearErrorHandler')
    {parent::init('tbl_mcqgenerator_exams',$pearDb,$errorCallback);$this->checked($this->objEngine->getDbObj()->setCharset('utf8mb4'));}
    public function one($id){return $this->run('SELECT * FROM tbl_mcqgenerator_exams WHERE id='.$this->q($id),true)[0]??null;}
    public function owned($owner,$page=1){return $this->run('SELECT * FROM tbl_mcqgenerator_exams WHERE ownerid='.$this->q($owner).' ORDER BY datemodified DESC,id DESC LIMIT '.((max(1,min(10000,(int)$page))-1)*20).',21',true);}
    public function createExam($owner,$title,array $content)
    {
        $id=bin2hex(random_bytes(16));$now=$this->getObject('timeanddateservice','timeanddate-service')->nowStorage();
        $values=[$id,$owner,$title,json_encode($content,JSON_THROW_ON_ERROR),1,$now,$now];
        $this->run('INSERT INTO tbl_mcqgenerator_exams (id,ownerid,title,content_json,version,datecreated,datemodified) VALUES ('.implode(',',array_map([$this,'q'],$values)).')');
        return $id;
    }
    public function saveExam(array $row,$title,array $content)
    {
        // Owner and revision form one atomic compare-and-swap; no lost edits or repeated additions.
        $changed=$this->run('UPDATE tbl_mcqgenerator_exams SET title='.$this->q($title).',content_json='.$this->q(json_encode($content,JSON_THROW_ON_ERROR)).',version=version+1,datemodified='.$this->q($this->getObject('timeanddateservice','timeanddate-service')->nowStorage()).' WHERE id='.$this->q($row['id']).' AND ownerid='.$this->q($row['ownerid']).' AND version='.(int)$row['version']);
        if($changed!==1)throw new DomainException('exam_changed');
    }
    public function removeOwned(array $row)
    {
        if($this->run('DELETE FROM tbl_mcqgenerator_exams WHERE id='.$this->q($row['id']).' AND ownerid='.$this->q($row['ownerid']).' AND version='.(int)$row['version'])!==1)throw new DomainException('exam_changed');
    }
    private function checked($result){if($result===false||is_object($result)&&is_a($result,'PEAR_Error'))throw new RuntimeException('storage_failed');return $result;}
    private function run($sql,$read=false){$db=$this->objEngine->getDbObj();$db->pushErrorHandling(PEAR_ERROR_RETURN);try{return $this->checked($read?$db->queryAll($sql):$db->exec($sql));}finally{$db->popErrorHandling();}}
    private function q($value){return (string)$value===''?"''":$this->objEngine->getDbObj()->quote((string)$value,'text');}
}
