<?php
/**
 * Persistence gateway for typed note attachments.
 *
 * @author Derek Keats
 * @package pagenotes
 */
if(empty($GLOBALS['kewl_entry_point_run']))die('You cannot view this page directly');
/** Stores durable backlinks between one note and many resources. */
class dbnotelinks extends dbTable
{
 /** Initialise the note-link table gateway. */
 public function init($tableName=null,$pearDb=null,$errorCallback='globalPearErrorHandler'){parent::init('tbl_pagenotes_links',$pearDb,$errorCallback);$this->query('SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci');}
 /** Return all attachments for a note. */
 public function forNote($noteId){$rows=$this->getAll('WHERE noteid='.$this->q($noteId).' ORDER BY datecreated,targetlabel');return is_array($rows)?$rows:array();}
 /** Return note identifiers attached to a typed target. */
 public function noteIdsForTarget($type,$id){$rows=$this->getAll('WHERE targettype='.$this->q($type).' AND targetid='.$this->q($id).' ORDER BY datecreated DESC');return array_values(array_filter(array_map(fn($row)=>$row['noteid']??null,is_array($rows)?$rows:array())));}
 /** Add an idempotent typed attachment. */
 public function addLink($noteId,$type,$id,$label,$url,$actor){$existing=$this->getAll('WHERE noteid='.$this->q($noteId).' AND targettype='.$this->q($type).' AND targetid='.$this->q($id).' LIMIT 1');if(isset($existing[0]))return $existing[0]['id'];$linkId=bin2hex(random_bytes(16));return $this->insert(array('id'=>$linkId,'noteid'=>$noteId,'targettype'=>$type,'targetid'=>$id,'targetlabel'=>$label,'targeturl'=>$url,'createdby'=>$actor,'datecreated'=>date('Y-m-d H:i:s')))===false?false:$linkId;}
 /** Remove one attachment belonging to a note. */
 public function removeLink($id,$noteId){$row=$this->getRow('id',$id);return is_array($row)&&hash_equals((string)$noteId,(string)$row['noteid'])?$this->delete('id',$id)!==false:false;}
 /** Quote a scalar for the current database driver. */
 private function q($value){$db=$this->objEngine->getDbObj();return method_exists($db,'quoteSmart')?$db->quoteSmart((string)$value):"'".str_replace("'","''",(string)$value)."'";}
}
?>
