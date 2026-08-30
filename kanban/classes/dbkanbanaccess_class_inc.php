<?php
if (empty($GLOBALS['kewl_entry_point_run'])) die('You cannot view this page directly');
class dbkanbanaccess extends dbTable
{
    public function init($tableName=null,$pearDb=null,$errorCallback='globalPearErrorHandler'){parent::init('tbl_kanban_access',$pearDb,$errorCallback);}
    public function grants($boardId){$rows=$this->getAll("WHERE boardid=".$this->q($boardId).' ORDER BY principaltype,principalid');return is_array($rows)?$rows:array();}
    public function userPermission($boardId,$userId){$rows=$this->getAll("WHERE boardid=".$this->q($boardId)." AND principaltype='user' AND principalid=".$this->q($userId).' LIMIT 1');return isset($rows[0])?$rows[0]['permission']:false;}
    public function replaceUserGrants($boardId,array $grants,$actor){$this->query("DELETE FROM tbl_kanban_access WHERE boardid=".$this->q($boardId)." AND principaltype='user'");foreach($grants as $userId=>$permission){if(!in_array($permission,array('view','edit','manage'),true))continue;$this->insert(array('id'=>bin2hex(random_bytes(16)),'boardid'=>$boardId,'principaltype'=>'user','principalid'=>$userId,'permission'=>$permission,'createdby'=>$actor,'datecreated'=>date('Y-m-d H:i:s')));}return true;}
    public function removeForBoard($boardId){return $this->query('DELETE FROM tbl_kanban_access WHERE boardid='.$this->q($boardId))!==false;}
    private function q($v){$db=$this->objEngine->getDbObj();return method_exists($db,'quoteSmart')?$db->quoteSmart((string)$v):"'".str_replace("'","''",(string)$v)."'";}
}
?>
