<?php
/** Persistence gateway for scheduled system notices. @author Derek Keats @package systemmanagement */
if (empty($GLOBALS['kewl_entry_point_run'])) die('You cannot view this page directly');
class dbsystemnotices extends dbTable
{
    /** Bind this gateway to its owned table. */
    public function init(){parent::init('tbl_systemmanagement_notices');}
    /** Return notices newest first for the administration console. */
    public function allNotices(){return $this->getAll(' ORDER BY datecreated DESC');}
    /** Return notices active at one canonical UTC storage instant. */
    public function activeAt($now){$q=$this->_objDb->quote($now);return $this->getAll(" WHERE (starts_at IS NULL OR starts_at <= {$q}) AND (ends_at IS NULL OR ends_at > {$q}) ORDER BY datecreated DESC");}
    /** Store a validated notice. */
    public function createNotice(array $data){return $this->insert($data);}
    /** Delete one notice by generated identifier. */
    public function removeNotice($id){return $this->delete('id',$id);}
    /** Return whether a user belongs to a lecturer group in any course. */
    public function userLectures($userId){$q=$this->_objDb->quote((string)$userId);$rows=$this->getArray("SELECT 1 FROM tbl_perms_groupusers gu JOIN tbl_perms_perm_users pu ON pu.perm_user_id=gu.perm_user_id JOIN tbl_perms_groups g ON g.group_id=gu.group_id WHERE pu.auth_user_id={$q} AND g.group_define_name LIKE '%^Lecturers' LIMIT 1");return !empty($rows);}
}
?>
