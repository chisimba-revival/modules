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
    public function init($tableName=null,$pearDb=null,$errorCallback='globalPearErrorHandler'){parent::init('tbl_knowledgemap_access',$pearDb,$errorCallback);$this->query('SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci');}

    /** Return all grants for a map. */
    public function grants($mapid) { return $this->selectPrepared('SELECT * FROM tbl_knowledgemap_access WHERE mapid=? ORDER BY principaltype,principalid', [$mapid]); }

    /** Return a directly invited user's permission. */
    public function userPermission($mapid,$userId) { $rows=$this->selectPrepared("SELECT permission FROM tbl_knowledgemap_access WHERE mapid=? AND principaltype='user' AND principalid=? LIMIT 1", [$mapid,$userId]); return $rows[0]['permission']??false; }

    /** Replace direct-user grants with a validated permission set. */
    public function replaceUserGrants($mapid,array $grants,$actor)
    {
        try { return $this->transactionWithSavepoint(function () use ($mapid,$grants,$actor) {
            $this->executePrepared("DELETE FROM tbl_knowledgemap_access WHERE mapid=? AND principaltype='user'", [$mapid]);
            foreach ($grants as $userId=>$permission) {
                if (!in_array($permission,['view','edit','manage'],true)) continue;
                if ($this->insert(['id'=>bin2hex(random_bytes(16)),'mapid'=>$mapid,'principaltype'=>'user','principalid'=>$userId,'permission'=>$permission,'createdby'=>$actor,'datecreated'=>date('Y-m-d H:i:s')])===false) throw new RuntimeException('Grant insertion failed');
            }
            return true;
        }); } catch (Throwable $error) { return false; }
    }

    /** Return the active opaque public-view token for a managed map. */
    public function publicLinkToken($mapid) { $rows=$this->selectPrepared("SELECT principalid FROM tbl_knowledgemap_access WHERE mapid=? AND principaltype='public_link' AND permission='view' LIMIT 1", [$mapid]); return $rows[0]['principalid']??false; }

    /** Resolve an opaque public-view token without granting any editable permission. */
    public function publicLinkMapId($token) { $rows=$this->selectPrepared("SELECT mapid FROM tbl_knowledgemap_access WHERE principaltype='public_link' AND principalid=? AND permission='view' LIMIT 1", [$token]); return $rows[0]['mapid']??false; }

    /** Atomically replace or revoke the sole public read-only link for a map. */
    public function replacePublicLink($mapid,$token,$actor)
    {
        try { return $this->transactionWithSavepoint(function () use ($mapid,$token,$actor) {
            $this->executePrepared("DELETE FROM tbl_knowledgemap_access WHERE mapid=? AND principaltype='public_link'", [$mapid]);
            if ($token!==null && $this->insert(['id'=>bin2hex(random_bytes(16)),'mapid'=>$mapid,'principaltype'=>'public_link','principalid'=>$token,'permission'=>'view','createdby'=>$actor,'datecreated'=>date('Y-m-d H:i:s')])===false) throw new RuntimeException('Public link insertion failed');
            return true;
        }); } catch (Throwable $error) { return false; }
    }

}
?>
