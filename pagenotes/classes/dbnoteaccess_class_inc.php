<?php
/**
 * Persistence gateway for note access grants.
 *
 * @author Derek Keats
 * @package pagenotes
 */
if(empty($GLOBALS['kewl_entry_point_run']))die('You cannot view this page directly');
/** Stores direct-user grants while reserving role and group principals. */
class dbnoteaccess extends dbTable
{
 /** Initialise the note access table gateway. */
 public function init($tableName=null,$pearDb=null,$errorCallback='globalPearErrorHandler'){parent::init('tbl_pagenotes_access',$pearDb,$errorCallback);$this->query('SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci');}
 /** Return all grants for a note. */
 public function grants($noteId){$rows=$this->selectPrepared('SELECT * FROM '.$this->_tableName.' '.('WHERE noteid=? ORDER BY principaltype,principalid'),[$noteId]);return is_array($rows)?$rows:array();}
 /** Return a directly invited user's permission. */
 public function userPermission($noteId,$userId){$rows=$this->selectPrepared('SELECT * FROM '.$this->_tableName.' '.("WHERE noteid=? AND principaltype='user' AND principalid=?".' LIMIT 1'),[$noteId,$userId]);return isset($rows[0])?$rows[0]['permission']:false;}
 /** Replace direct-user grants with a validated permission set. */
 public function replaceUserGrants($noteId,array $grants,$actor){$this->executePrepared('DELETE FROM tbl_pagenotes_access WHERE noteid=?'." AND principaltype='user'",[$noteId]);foreach($grants as $userId=>$permission){if(!in_array($permission,array('view','edit','manage'),true))continue;$this->insert(array('id'=>bin2hex(random_bytes(16)),'noteid'=>$noteId,'principaltype'=>'user','principalid'=>$userId,'permission'=>$permission,'createdby'=>$actor,'datecreated'=>date('Y-m-d H:i:s')));}return true;}
 /** Quote a scalar for the current database driver. */

}
?>
