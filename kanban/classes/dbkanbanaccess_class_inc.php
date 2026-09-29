<?php
if (empty($GLOBALS['kewl_entry_point_run'])) die('You cannot view this page directly');
class dbkanbanaccess extends dbTable
{
    public function init($tableName=null,$pearDb=null,$errorCallback='globalPearErrorHandler'){parent::init('tbl_kanban_access',$pearDb,$errorCallback);}
    public function grants($boardId){$rows=$this->getAll("WHERE boardid=".$this->q($boardId).' ORDER BY principaltype,principalid');return is_array($rows)?$rows:array();}
    public function userPermission($boardId,$userId){$rows=$this->getAll("WHERE boardid=".$this->q($boardId)." AND principaltype='user' AND principalid=".$this->q($userId).' LIMIT 1');return isset($rows[0])?$rows[0]['permission']:false;}
    public function replaceUserGrants($boardId,array $grants,$actor){
        if ($this->query('START TRANSACTION') === false) return false;
        try {
            if ($this->query("DELETE FROM tbl_kanban_access WHERE boardid=".$this->q($boardId)." AND principaltype='user'") === false) throw new RuntimeException('Grant deletion failed');
            foreach ($grants as $userId=>$permission) {
                if (!in_array($permission,array('view','edit','manage'),true)) continue;
                if ($this->insert(array('id'=>bin2hex(random_bytes(16)),'boardid'=>$boardId,'principaltype'=>'user','principalid'=>$userId,'permission'=>$permission,'createdby'=>$actor,'datecreated'=>date('Y-m-d H:i:s'))) === false) throw new RuntimeException('Grant insertion failed');
            }
            if ($this->query('COMMIT') === false) throw new RuntimeException('Grant commit failed');
            return true;
        } catch (Throwable $error) { $this->query('ROLLBACK'); return false; }
    }
    public function publicLinkToken($boardId){$rows=$this->getAll("WHERE boardid=".$this->q($boardId)." AND principaltype='public_link' AND permission='view' LIMIT 1");return isset($rows[0]['principalid'])?$rows[0]['principalid']:false;}
    public function publicLinkBoardId($token){$rows=$this->getAll("WHERE principaltype='public_link' AND principalid=".$this->q($token)." AND permission='view' LIMIT 1");return isset($rows[0]['boardid'])?$rows[0]['boardid']:false;}
    public function replacePublicLink($boardId,$token,$actor){
        try {
            if ($this->query('BEGIN') === false) throw new RuntimeException('Transaction start failed');
            if ($this->query("DELETE FROM tbl_kanban_access WHERE boardid=".$this->q($boardId)." AND principaltype='public_link'") === false) throw new RuntimeException('Public link deletion failed');
            if ($token!==null && $this->insert(array('id'=>bin2hex(random_bytes(16)),'boardid'=>$boardId,'principaltype'=>'public_link','principalid'=>$token,'permission'=>'view','createdby'=>$actor,'datecreated'=>date('Y-m-d H:i:s'))) === false) throw new RuntimeException('Public link insertion failed');
            if ($this->query('COMMIT') === false) throw new RuntimeException('Transaction commit failed');
            return true;
        } catch (Throwable $error) { $this->query('ROLLBACK'); return false; }
    }
    public function removeForBoard($boardId){return $this->query('DELETE FROM tbl_kanban_access WHERE boardid='.$this->q($boardId))!==false;}
    private function q($v){$db=$this->objEngine->getDbObj();return method_exists($db,'quoteSmart')?$db->quoteSmart((string)$v):"'".str_replace("'","''",(string)$v)."'";}
}
?>
