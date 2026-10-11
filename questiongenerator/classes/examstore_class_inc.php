<?php
/** Versioned private exam snapshots. @author Derek Keats */
if(empty($GLOBALS['kewl_entry_point_run']))die('No direct access');
class examstore extends dbTable
{
    public function init($tableName=null,$pearDb=null,$errorCallback='globalPearErrorHandler')
    { parent::init('tbl_mcqgenerator_exams',$pearDb,$errorCallback); $this->executeExactPrepared('SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci'); }
    public function one($id){return $this->run('SELECT * FROM tbl_mcqgenerator_exams WHERE id=?',[$id],true)[0]??null;}
    public function owned($owner,$page=1){return $this->run('SELECT e.*,(SELECT COUNT(*) FROM tbl_questionworkshop_sets s WHERE s.examid=e.id AND s.ownerid=e.ownerid) AS chapter_count FROM tbl_mcqgenerator_exams e WHERE ownerid=? ORDER BY datemodified DESC,id DESC LIMIT '.((max(1,min(10000,(int)$page))-1)*20).',21',[$owner],true);}
    public function createExam($owner,$title,array $content) {
        $id=bin2hex(random_bytes(16)); $now=$this->getObject('timeanddateservice','timeanddate-service')->nowStorage();
        $this->executeExactPrepared('INSERT INTO tbl_mcqgenerator_exams (id,ownerid,title,content_json,version,datecreated,datemodified) VALUES (?,?,?,?,?,?,?)',[$id,$owner,$title,json_encode($content,JSON_THROW_ON_ERROR),1,$now,$now]);
        return $id;
    }
    public function saveExam(array $row,$title,array $content)
    {
        // Owner and revision form one atomic compare-and-swap; no lost edits or repeated additions.
        $changed=$this->run('UPDATE tbl_mcqgenerator_exams SET title=?,content_json=?,version=version+1,datemodified=? WHERE id=? AND ownerid=? AND version='.(int)$row['version'],[$title,json_encode($content,JSON_THROW_ON_ERROR),$this->getObject('timeanddateservice','timeanddate-service')->nowStorage(),$row['id'],$row['ownerid']]);
        if($changed!==1)throw new DomainException('exam_changed');
    }
    public function removeOwned(array $row)
    {
        return $this->transaction(function () use ($row) {
            $this->run('SELECT id FROM tbl_mcqgenerator_exams WHERE id=? FOR UPDATE',[$row['id']],true);
            if($this->run('SELECT id FROM tbl_questionworkshop_sets WHERE examid=? LIMIT 1',[$row['id']],true))throw new DomainException('exam_has_chapters');
            if($this->run('DELETE FROM tbl_mcqgenerator_exams WHERE id=? AND ownerid=? AND version='.(int)$row['version'],[$row['id'],$row['ownerid']])!==1)throw new DomainException('exam_changed');

        });
    }
    /** Idempotent installation migration: preserve all source data and saved papers. */
    public function assignLegacySets()
    {
        return $this->transaction(function () {
            $sets=$this->run("SELECT id,ownerid FROM tbl_questionworkshop_sets WHERE examid='' OR examid IS NULL ORDER BY ownerid,id FOR UPDATE",[],true);
            $parents=[];
            foreach($sets as $set){
                $owner=$set['ownerid'];
                if(!isset($parents[$owner])){
                    $existing=$this->run('SELECT id FROM tbl_mcqgenerator_exams WHERE ownerid=? ORDER BY datecreated,id LIMIT 1',[$owner],true);
                    $parents[$owner]=$existing?$existing[0]['id']:$this->createExam($owner,$this->getObject('workshoprenderer','questiongenerator')->text('exam_legacy'),$this->getObject('examservice','questiongenerator')->emptyContent());
                }
                $this->run('UPDATE tbl_questionworkshop_sets SET examid=? WHERE id=?',[$parents[$owner],$set['id']]);
            }

        });
    }

    private function run($sql,array $params=[],$read=false) {
        return $read ? $this->selectPrepared($sql,$params) : $this->executeExactPrepared($sql,$params);
    }

}
