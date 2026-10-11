<?php
/** Bank persistence through the canonical connection, with serialised writes. @author Derek Keats */
if (empty($GLOBALS['kewl_entry_point_run'])) die('No direct access');
class bankstore extends dbTable
{
    public function init($tableName=null,$pearDb=null,$errorCallback='globalPearErrorHandler') { parent::init('tbl_mcq_banks',$pearDb,$errorCallback); }

    public function run($sql,$read=false,array $params=[]) {return $read?$this->selectPrepared($sql,$params):$this->executeExactPrepared($sql,$params);}
    public function rows($sql,array $params=[]) {return $this->selectPrepared($sql,$params);}
    public function bank($id,$lock=false) { return $this->rows('SELECT * FROM tbl_mcq_banks WHERE id=?'.($lock?' FOR UPDATE':''),[$id])[0]??null; }
    public function banks() { return $this->rows('SELECT * FROM tbl_mcq_banks ORDER BY name,id'); }
    public function items($bank) { return $this->rows('SELECT * FROM tbl_mcq_bank_items WHERE bankid=? ORDER BY id',[$bank]); }
    public function atomic(callable $work) {return $this->transaction($work);}
    public function addBank(array $shares,$name,$owner)
    {
        $id=bin2hex(random_bytes(16));
        $this->run('INSERT INTO tbl_mcq_banks (id,managers_json,name,shares_json,version,createdby) VALUES (?,?,?,?,1,?)',false,[$id,'[]',$name,json_encode($shares,JSON_THROW_ON_ERROR),$owner]);
        return $id;
    }
    public function addItem($bank,array $content,$fingerprint,$stemkey) {$id=bin2hex(random_bytes(16));$this->executeExactPrepared('INSERT INTO tbl_mcq_bank_items (id,bankid,fingerprint,stemkey,content_json) VALUES (?,?,?,?,?)',[$id,$bank,$fingerprint,$stemkey,json_encode($content,JSON_THROW_ON_ERROR)]);return $id;}
    public function metadata($id) { $row=$this->rows('SELECT content_json FROM tbl_mcq_question_meta WHERE id=?',[$id]); return $row?json_decode($row[0]['content_json'],true,512,JSON_THROW_ON_ERROR):[]; }
    public function saveMetadata($id,array $content)
    {
        $this->run('INSERT INTO tbl_mcq_question_meta (id,content_json) VALUES (?,?)',false,[$id,json_encode($content,JSON_THROW_ON_ERROR)]);
    }
}
