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
 public function inScope($type,$id,$archived=false){$rows=$this->getAll('WHERE scopetype='.$this->q($type).' AND scopeid='.$this->q($id).($archived?'':' AND isarchived=0').' ORDER BY datemodified DESC,title');return is_array($rows)?$rows:array();}
 /** Return active notes owned by a user. */
 public function ownedBy($userId,$archived=false){$rows=$this->getAll('WHERE ownerid='.$this->q($userId).($archived?'':' AND isarchived=0').' ORDER BY datemodified DESC,title');return is_array($rows)?$rows:array();}
 /** Return notes shared directly with a user. */
 public function sharedWith($userId,$archived=false){$sql="SELECT n.* FROM tbl_pagenotes_items n INNER JOIN tbl_pagenotes_access a ON a.noteid=n.id WHERE a.principaltype='user' AND a.principalid=".$this->q($userId).($archived?'':' AND n.isarchived=0').' ORDER BY n.datemodified DESC,n.title';$rows=$this->getArray($sql);return is_array($rows)?$rows:array();}
 /** Create a note and return its generated identifier. */
 public function createNote(array $data){$now=date('Y-m-d H:i:s');$id=bin2hex(random_bytes(16));$data=array_merge(array('id'=>$id,'body'=>'','isarchived'=>0),$data,array('id'=>$id,'datecreated'=>$now,'datemodified'=>$now));return $this->insert($data)===false?false:$id;}
 /** Update editable note fields. */
 public function saveNote($id,array $data){$allowed=array_intersect_key($data,array_flip(array('title','body','isarchived')));$allowed['datemodified']=date('Y-m-d H:i:s');return $this->update('id',$id,$allowed)!==false;}
 /** Quote a scalar for the current database driver. */
 private function q($value){$db=$this->objEngine->getDbObj();return method_exists($db,'quoteSmart')?$db->quoteSmart((string)$value):"'".str_replace("'","''",(string)$value)."'";}
}
?>
