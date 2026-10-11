<?php
/**
 * Persistence gateway for canonical notes.
 *
 * @author Derek Keats
 * @package pagenotes
 */
if(empty($GLOBALS['kewl_entry_point_run']))die('You cannot view this page directly');
/** Stores note content, ownership and scope. */
class dbnotes extends dbTable
{
 /** Initialise the note table gateway. */
 public function init($tableName=null,$pearDb=null,$errorCallback='globalPearErrorHandler'){parent::init('tbl_pagenotes_items',$pearDb,$errorCallback);$this->query('SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci');}
 /** Return one note or false. */
 public function one($id){$row=$this->getRow('id',(string)$id);return is_array($row)?$row:false;}
 /** Return active notes in one scope. */
 public function inScope($type,$id,$archived=false){$rows=$this->selectPrepared('SELECT * FROM '.$this->_tableName.' '.('WHERE scopetype=? AND scopeid=?'.($archived?'':' AND isarchived=0').' ORDER BY datemodified DESC,title'),[$type,$id]);return is_array($rows)?$rows:array();}
 /** Return active notes owned by a user. */
 public function ownedBy($userId,$archived=false){$rows=$this->selectPrepared('SELECT * FROM '.$this->_tableName.' '.('WHERE ownerid=?'.($archived?'':' AND isarchived=0').' ORDER BY datemodified DESC,title'),[$userId]);return is_array($rows)?$rows:array();}
 /** Return notes shared directly with a user. */
 public function sharedWith($userId,$archived=false){$params=[]; $sql="SELECT n.* FROM tbl_pagenotes_items n INNER JOIN tbl_pagenotes_access a ON a.noteid=n.id WHERE a.principaltype='user' AND a.principalid=".$this->bindValue($userId,$params).($archived?'':' AND n.isarchived=0').' ORDER BY n.datemodified DESC,n.title';$rows=$this->selectPrepared($sql,$params);return is_array($rows)?$rows:array();}
 /** Create a note and return its generated identifier. */
 public function createNote(array $data){$now=date('Y-m-d H:i:s');$id=bin2hex(random_bytes(16));$data=array_merge(array('id'=>$id,'body'=>'','isarchived'=>0),$data,array('id'=>$id,'datecreated'=>$now,'datemodified'=>$now));return $this->insert($data)===false?false:$id;}
 /** Update editable note fields. */
 public function saveNote($id,array $data){$allowed=array_intersect_key($data,array_flip(array('title','body','isarchived')));$allowed['datemodified']=date('Y-m-d H:i:s');return $this->update('id',$id,$allowed)!==false;}
 /** Quote a scalar for the current database driver. */
 private function bindValue($value,array &$params) { $params[]=(string)$value; return '?'; }
}
?>
