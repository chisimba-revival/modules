<?php
/**
 * Persistence gateway for Active Knowledge Map access grants.
 *
 * @author Derek Keats
 * @package knowledgemap
 */
if (empty($GLOBALS['kewl_entry_point_run'])) die('You cannot view this page directly');

/** Stores invited-user grants while retaining a future principal seam. */
class dbknowledgemapaccess extends dbTable
{
    /** Initialise the access table gateway. */
    public function init($tableName=null,$pearDb=null,$errorCallback='globalPearErrorHandler'){parent::init('tbl_knowledgemap_access',$pearDb,$errorCallback);}

    /** Return all grants for a map. */
    public function grants($mapId){$rows=$this->getAll('WHERE mapid='.$this->q($mapId).' ORDER BY principaltype,principalid');return is_array($rows)?$rows:array();}

    /** Return a directly invited user's permission. */
    public function userPermission($mapId,$userId){$rows=$this->getAll("WHERE mapid=".$this->q($mapId)." AND principaltype='user' AND principalid=".$this->q($userId).' LIMIT 1');return isset($rows[0])?$rows[0]['permission']:false;}

    /** Replace direct-user grants with a validated permission set. */
    public function replaceUserGrants($mapId,array $grants,$actor){$this->query('DELETE FROM tbl_knowledgemap_access WHERE mapid='.$this->q($mapId)." AND principaltype='user'");foreach($grants as $userId=>$permission){if(!in_array($permission,array('view','edit','manage'),true))continue;$this->insert(array('id'=>bin2hex(random_bytes(16)),'mapid'=>$mapId,'principaltype'=>'user','principalid'=>$userId,'permission'=>$permission,'createdby'=>$actor,'datecreated'=>date('Y-m-d H:i:s')));}return true;}

    /** Quote one scalar for the active database driver. */
    private function q($value){$db=$this->objEngine->getDbObj();return method_exists($db,'quoteSmart')?$db->quoteSmart((string)$value):"'".str_replace("'","''",(string)$value)."'";}
}
?>
