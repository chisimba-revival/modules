<?php
/** Bank persistence through the canonical connection, with serialised writes. @author Derek Keats */
if (empty($GLOBALS['kewl_entry_point_run'])) die('No direct access');
class bankstore extends dbTable
{
    public function init($tableName=null,$pearDb=null,$errorCallback='globalPearErrorHandler') { parent::init('tbl_mcq_banks',$pearDb,$errorCallback); }
    public function q($value) { return $this->objEngine->getDbObj()->quote((string)$value,'text'); }
    public function run($sql, $read=false)
    {
        $db=$this->objEngine->getDbObj(); $db->pushErrorHandling(PEAR_ERROR_RETURN);
        try {
            $result=$read ? $db->queryAll($sql) : $db->exec($sql);
            if ($result===false || PEAR::isError($result)) throw new RuntimeException('bank_storage');
            return $result;
        } finally { $db->popErrorHandling(); }
    }
    public function rows($sql) { return $this->run($sql,true); }
    public function bank($id,$lock=false) { return $this->rows('SELECT * FROM tbl_mcq_banks WHERE id='.$this->q($id).($lock?' FOR UPDATE':''))[0]??null; }
    public function banks() { return $this->rows('SELECT * FROM tbl_mcq_banks ORDER BY name,id'); }
    public function items($bank) { return $this->rows('SELECT * FROM tbl_mcq_bank_items WHERE bankid='.$this->q($bank).' ORDER BY id'); }
    public function atomic(callable $work)
    {
        $this->beginTransaction();
        try { $result=$work(); $this->commitTransaction(); return $result; }
        catch (Throwable $error) { $this->rollbackTransaction(); throw $error; }
    }
    public function addBank(array $shares,$name,$owner)
    {
        $id=bin2hex(random_bytes(16));
        $this->run('INSERT INTO tbl_mcq_banks (id,managers_json,name,shares_json,version,createdby) VALUES ('.$this->q($id).','.$this->q('[]').','.$this->q($name).','.$this->q(json_encode($shares,JSON_THROW_ON_ERROR)).',1,'.$this->q($owner).')');
        return $id;
    }
    public function addItem($bank,array $content,$fingerprint,$stemkey)
    {
        $id=bin2hex(random_bytes(16));
        $this->run('INSERT INTO tbl_mcq_bank_items (id,bankid,fingerprint,stemkey,content_json) VALUES ('.implode(',',array_map([$this,'q'],[$id,$bank,$fingerprint,$stemkey,json_encode($content,JSON_THROW_ON_ERROR)])).')');
        return $id;
    }
    public function metadata($id) { $row=$this->rows('SELECT content_json FROM tbl_mcq_question_meta WHERE id='.$this->q($id)); return $row?json_decode($row[0]['content_json'],true,512,JSON_THROW_ON_ERROR):[]; }
    public function saveMetadata($id,array $content)
    {
        $this->run('INSERT INTO tbl_mcq_question_meta (id,content_json) VALUES ('.$this->q($id).','.$this->q(json_encode($content,JSON_THROW_ON_ERROR)).')');
    }
}
