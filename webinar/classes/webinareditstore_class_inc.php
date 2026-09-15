<?php
/** Checked transactional writes, shared with classification savepoints. @author Derek Keats */
if(empty($GLOBALS['kewl_entry_point_run']))die('No direct access');
class webinareditstore extends dbTable
{
    public function init($tableName=null,$pearDb=null,$errorCallback='globalPearErrorHandler')
    { parent::init('tbl_webinar_records',$pearDb,$errorCallback); }
    private function db(){return $this->objEngine->getDbObj();}
    private function q($value){return $value===''?"''":$this->db()->quote($value);}
    private function rows($sql)
    {
        $result=$this->db()->query($sql);
        if(PEAR::isError($result))throw new RuntimeException('webinar_storage_failed');
        return $result->fetchAll(MDB2_FETCHMODE_ASSOC);
    }
    private function execute($sql)
    {
        $result=$this->db()->exec($sql);
        if(PEAR::isError($result))throw new RuntimeException('webinar_storage_failed');
        return $result;
    }
    /** Installation/upgrade only; duplicate identities fail instead of being discarded. */
    public function prepareSchema()
    {
        $status=$this->rows("SHOW TABLE STATUS WHERE Name='tbl_webinar_records'");
        if(!$status)throw new RuntimeException('webinar_setup_required');
        if($status[0]['engine']!=='InnoDB')$this->execute('ALTER TABLE tbl_webinar_records ENGINE=InnoDB');
        $indexes=$this->rows('SHOW INDEX FROM tbl_webinar_records');
        foreach(['webinar_id_unique'=>'id','webinar_source_unique'=>'source_key'] as $name=>$column){
            $matches=array_values(array_filter($indexes,fn($index)=>$index['key_name']===$name));
            if($matches){
                if(count($matches)!==1||$matches[0]['non_unique']||$matches[0]['column_name']!==$column)throw new RuntimeException('webinar_setup_required');
            }else $this->execute('ALTER TABLE tbl_webinar_records ADD UNIQUE KEY '.$name.' ('.$column.')');
        }
    }
    public function begin()
    {
        $status=$this->rows("SHOW TABLE STATUS WHERE Name='tbl_webinar_records'");
        $indexes=$this->rows('SHOW INDEX FROM tbl_webinar_records');$unique=false;
        foreach($indexes as $index)if($index['key_name']==='webinar_id_unique'&&!$index['non_unique'])$unique=true;
        if(($status[0]['engine']??'')!=='InnoDB'||!$unique)throw new RuntimeException('webinar_setup_required');
        $this->execute('START TRANSACTION');
    }
    public function commit(){$this->execute('COMMIT');}
    public function rollback(){$this->execute('ROLLBACK');}
    public function record($id,$lock=false)
    {
        if(!is_string($id)||!preg_match('/^[a-f0-9]{32}$/D',$id))return null;
        $rows=$this->rows('SELECT * FROM tbl_webinar_records WHERE id='.$this->q($id).($lock?' FOR UPDATE':''));
        return $rows[0]??null;
    }
    public function listing($kind='webinar')
    {
        if(!in_array($kind,['webinar','speaker'],true))return [];
        return $this->rows('SELECT * FROM tbl_webinar_records WHERE kind='.$this->q($kind).' ORDER BY presented_at DESC,title,id');
    }
    public function persist(array $record,$existing)
    {
        $allowed=['id','kind','source_key','source_hash','title','status','presented_at','payload'];
        $record=array_intersect_key($record,array_flip($allowed));
        if($existing){$id=$record['id'];unset($record['id']);$sets=[];foreach($record as $key=>$value)$sets[]=$key.'='.$this->q($value);$this->execute('UPDATE tbl_webinar_records SET '.implode(',',$sets).' WHERE id='.$this->q($id));}
        else $this->execute('INSERT INTO tbl_webinar_records ('.implode(',',array_keys($record)).') VALUES ('.implode(',',array_map(fn($v)=>$this->q($v),array_values($record))).')');
    }
}
