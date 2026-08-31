<?php
/**
 * Persistence gateway for scoped Active Knowledge Maps.
 *
 * @author Derek Keats
 * @package knowledgemap
 */
if (empty($GLOBALS['kewl_entry_point_run'])) die('You cannot view this page directly');

/**
 * Stores map identity, scope, ownership, revision and migration provenance.
 *
 * @author Derek Keats
 * @package knowledgemap
 */
class dbknowledgemaps extends dbTable
{
    /** Initialise the map table gateway. */
    public function init($tableName=null,$pearDb=null,$errorCallback='globalPearErrorHandler'){parent::init('tbl_knowledgemap_maps',$pearDb,$errorCallback);}

    /** Return one map or false. */
    public function one($id){$row=$this->getRow('id',(string)$id);return is_array($row)?$row:false;}

    /** Return maps in one explicit scope. */
    public function inScope($type,$id){$rows=$this->getAll('WHERE scopetype='.$this->q($type).' AND scopeid='.$this->q($id).' ORDER BY datemodified DESC,title');return is_array($rows)?$rows:array();}

    /** Return maps owned by a user. */
    public function ownedBy($userId){$rows=$this->getAll('WHERE ownerid='.$this->q($userId).' ORDER BY datemodified DESC,title');return is_array($rows)?$rows:array();}

    /** Return maps shared directly with a user. */
    public function sharedWith($userId){$sql="SELECT m.* FROM tbl_knowledgemap_maps m INNER JOIN tbl_knowledgemap_access a ON a.mapid=m.id WHERE a.principaltype='user' AND a.principalid=".$this->q($userId).' ORDER BY m.datemodified DESC,m.title';$rows=$this->getArray($sql);return is_array($rows)?$rows:array();}

    /** Find an earlier migration of an identical source document. */
    public function bySourceFingerprint($fingerprint,$scopeType,$scopeId,$ownerId){$rows=$this->getAll('WHERE sourcefingerprint='.$this->q($fingerprint).' AND scopetype='.$this->q($scopeType).' AND scopeid='.$this->q($scopeId).' AND ownerid='.$this->q($ownerId).' LIMIT 1');return isset($rows[0])?$rows[0]:false;}

    /** Lock and return a map only when its revision is still current. */
    public function lockAtRevision($id,$revision){$rows=$this->getArray('SELECT * FROM tbl_knowledgemap_maps WHERE id='.$this->q($id).' AND revision='.(int)$revision.' FOR UPDATE');return isset($rows[0])?$rows[0]:false;}

    /** Create a map record and return its generated identifier. */
    public function createMap(array $data){$now=date('Y-m-d H:i:s');$id=bin2hex(random_bytes(16));$data=array_merge(array('id'=>$id,'rootnodeid'=>'','description'=>'','revision'=>1,'sourceformat'=>'','sourcefingerprint'=>'','sourcemetadata'=>''),$data,array('id'=>$id,'datecreated'=>$now,'datemodified'=>$now));$this->insert($data);return $id;}

    /** Update a map only when the caller holds the expected revision. */
    public function updateAtRevision($id,$revision,array $data){$allowed=array('rootnodeid','title','description');$assignments=array();foreach($allowed as $field)if(array_key_exists($field,$data))$assignments[]=$field.'='.$this->q($data[$field]);$assignments[]='revision='.((int)$revision+1);$assignments[]='datemodified='.$this->q(date('Y-m-d H:i:s'));$this->query('UPDATE tbl_knowledgemap_maps SET '.implode(',',$assignments).' WHERE id='.$this->q($id).' AND revision='.(int)$revision);return true;}

    /** Update map metadata inside a caller-managed transaction. */
    public function updateMap($id,array $data){$data['datemodified']=date('Y-m-d H:i:s');return $this->update('id',$id,$data)!==false;}

    /** Remove a map record. */
    public function removeMap($id){return $this->delete('id',$id)!==false;}

    /** Begin a storage transaction. */
    public function beginTransaction(){return $this->query('START TRANSACTION');}

    /** Commit a storage transaction. */
    public function commitTransaction(){return $this->query('COMMIT');}

    /** Roll back a storage transaction. */
    public function rollbackTransaction(){return $this->query('ROLLBACK');}

    /** Quote one scalar for the active database driver. */
    private function q($value){$db=$this->objEngine->getDbObj();return method_exists($db,'quoteSmart')?$db->quoteSmart((string)$value):"'".str_replace("'","''",(string)$value)."'";}
}
?>
