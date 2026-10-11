<?php
if (empty($GLOBALS['kewl_entry_point_run'])) die('You cannot view this page directly');
class dbkanbanaccess extends dbTable
{
    public function init($tableName=null,$pearDb=null,$errorCallback='globalPearErrorHandler'){parent::init('tbl_kanban_access',$pearDb,$errorCallback);}
    public function grants($boardid) { return $this->selectPrepared('SELECT * FROM tbl_kanban_access WHERE boardid=? ORDER BY principaltype,principalid', [$boardid]); }
    public function userPermission($boardid,$userId) { $rows=$this->selectPrepared("SELECT permission FROM tbl_kanban_access WHERE boardid=? AND principaltype='user' AND principalid=? LIMIT 1", [$boardid,$userId]); return $rows[0]['permission']??false; }
    public function replaceUserGrants($boardid,array $grants,$actor)
    {
        try { return $this->transactionWithSavepoint(function () use ($boardid,$grants,$actor) {
            $this->executePrepared("DELETE FROM tbl_kanban_access WHERE boardid=? AND principaltype='user'", [$boardid]);
            foreach ($grants as $userId=>$permission) {
                if (!in_array($permission,['view','edit','manage'],true)) continue;
                if ($this->insert(['id'=>bin2hex(random_bytes(16)),'boardid'=>$boardid,'principaltype'=>'user','principalid'=>$userId,'permission'=>$permission,'createdby'=>$actor,'datecreated'=>date('Y-m-d H:i:s')])===false) throw new RuntimeException('Grant insertion failed');
            }
            return true;
        }); } catch (Throwable $error) { return false; }
    }
    public function publicLinkToken($boardid) { $rows=$this->selectPrepared("SELECT principalid FROM tbl_kanban_access WHERE boardid=? AND principaltype='public_link' AND permission='view' LIMIT 1", [$boardid]); return $rows[0]['principalid']??false; }
    public function publicLinkBoardId($token) { $rows=$this->selectPrepared("SELECT boardid FROM tbl_kanban_access WHERE principaltype='public_link' AND principalid=? AND permission='view' LIMIT 1", [$token]); return $rows[0]['boardid']??false; }
    public function replacePublicLink($boardid,$token,$actor)
    {
        try { return $this->transactionWithSavepoint(function () use ($boardid,$token,$actor) {
            $this->executePrepared("DELETE FROM tbl_kanban_access WHERE boardid=? AND principaltype='public_link'", [$boardid]);
            if ($token!==null && $this->insert(['id'=>bin2hex(random_bytes(16)),'boardid'=>$boardid,'principaltype'=>'public_link','principalid'=>$token,'permission'=>'view','createdby'=>$actor,'datecreated'=>date('Y-m-d H:i:s')])===false) throw new RuntimeException('Public link insertion failed');
            return true;
        }); } catch (Throwable $error) { return false; }
    }
    public function removeForBoard($boardId) { $this->executePrepared('DELETE FROM tbl_kanban_access WHERE boardid=?', [$boardId]); return true; }

}
?>
