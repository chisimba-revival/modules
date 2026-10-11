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
    public function init($tableName=null,$pearDb=null,$errorCallback='globalPearErrorHandler'){parent::init('tbl_knowledgemap_maps',$pearDb,$errorCallback);$this->query('SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci');}

    /** Return one map or false. */
    public function one($id){$row=$this->getRow('id',(string)$id);return is_array($row)?$row:false;}

    /** Return maps in one explicit scope. */
    public function inScope($type,$id) { $rows=$this->selectPrepared("SELECT * FROM tbl_knowledgemap_maps WHERE scopetype=? AND scopeid=? ORDER BY datemodified DESC,title", [$type,$id]); return $rows; }

    /** Return maps owned by a user. */
    public function ownedBy($userId) { $rows=$this->selectPrepared("SELECT * FROM tbl_knowledgemap_maps WHERE ownerid=? ORDER BY datemodified DESC,title", [$userId]); return $rows; }

    /** Return maps shared directly with a user. */
    public function sharedWith($userId) { $rows=$this->selectPrepared("SELECT m.* FROM tbl_knowledgemap_maps m INNER JOIN tbl_knowledgemap_access a ON a.mapid=m.id WHERE a.principaltype='user' AND a.principalid=? ORDER BY m.datemodified DESC,m.title", [$userId]); return $rows; }

    /** Find an earlier migration of an identical source document. */
    public function bySourceFingerprint($fingerprint,$scopeType,$scopeId,$ownerId) { $rows=$this->selectPrepared("SELECT * FROM tbl_knowledgemap_maps WHERE sourcefingerprint=? AND scopetype=? AND scopeid=? AND ownerid=? LIMIT 1", [$fingerprint,$scopeType,$scopeId,$ownerId]); return $rows[0]??false; }

    /** Lock and return a map only when its revision is still current. */
    public function lockAtRevision($id,$revision) { $rows=$this->selectPrepared("SELECT * FROM tbl_knowledgemap_maps WHERE id=? AND revision=? FOR UPDATE", [$id,(int)$revision]); return $rows[0]??false; }

    /** Create a map record and return its generated identifier. */
    public function createMap(array $data) {
        $now=date('Y-m-d H:i:s'); $id=bin2hex(random_bytes(16));
        $data=array_merge(['id'=>$id,'rootnodeid'=>'','description'=>'','revision'=>1,'sourceformat'=>'','sourcefingerprint'=>'','sourcemetadata'=>''],$data,['id'=>$id,'datecreated'=>$now,'datemodified'=>$now]);
        if ($this->insert($data)===false) throw new RuntimeException('Map insertion failed');
        return $id;
    }

    /** Update a map only when the caller holds the expected revision. */
    public function updateAtRevision($id,$revision,array $data) {
        $assignments=[]; $params=[];
        foreach (['rootnodeid','title','description'] as $field) if (array_key_exists($field,$data)) { $assignments[]=$field.'=?'; $params[]=$data[$field]; }
        $assignments[]='revision=revision+1'; $assignments[]='datemodified=?';
        array_push($params,date('Y-m-d H:i:s'),$id,(int)$revision);
        if ($this->executePrepared('UPDATE tbl_knowledgemap_maps SET '.implode(',',$assignments).' WHERE id=? AND revision=?',$params)!==1) throw new DomainException('The map revision changed.');
        return true;
    }

    /** Update map metadata inside a caller-managed transaction. */
    public function updateMap($id,array $data){$data['datemodified']=date('Y-m-d H:i:s');return $this->update('id',$id,$data)!==false;}

    /** Remove a map record. */
    public function removeMap($id){return $this->delete('id',$id)!==false;}




}
?>
