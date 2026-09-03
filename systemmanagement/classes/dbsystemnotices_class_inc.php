<?php
/** Persistence gateway for scheduled system notices. @author Derek Keats @package systemmanagement */
if (empty($GLOBALS['kewl_entry_point_run'])) die('You cannot view this page directly');
class dbsystemnotices extends dbTable
{
    /** Bind this gateway to its owned table. */
    public function init(
        $tableName = null,
        $pearDb = null,
        $errorCallback = 'globalPearErrorCallback'
    ) {
        parent::init('tbl_systemmanagement_notices');
    }
    /** Return notices newest first for the administration console. */
    public function allNotices(){return $this->getAll(' ORDER BY datecreated DESC');}
    /** Return notices active at one canonical UTC storage instant. */
    public function activeAt($now){$q=$this->quoteValue($now);return $this->getAll(" WHERE (starts_at IS NULL OR starts_at <= {$q}) AND (ends_at IS NULL OR ends_at > {$q}) ORDER BY datecreated DESC");}
    /** Store a validated notice. */
    public function createNotice(array $data){return $this->insert($data);}
    /** Delete one notice by generated identifier. */
    public function removeNotice($id){return $this->delete('id',$id);}
    /** Return whether a user belongs to a lecturer group in any course. */
    public function userLectures($userId){$q=$this->quoteValue($userId);$rows=$this->getArray("SELECT 1 FROM tbl_perms_groupusers gu JOIN tbl_perms_perm_users pu ON pu.perm_user_id=gu.perm_user_id JOIN tbl_perms_groups g ON g.group_id=gu.group_id WHERE pu.auth_user_id={$q} AND g.group_define_name LIKE '%^Lecturers' LIMIT 1");return !empty($rows);}
    /** Return unique active-user email addresses for one supported audience. */
    public function recipientEmails($audience){$audience=(string)$audience;$join='';$where="u.isactive=1 AND u.emailaddress IS NOT NULL AND TRIM(u.emailaddress)<>''";if($audience!=='everyone'){$join=' JOIN tbl_perms_perm_users pu ON pu.auth_user_id=u.userid JOIN tbl_perms_groupusers gu ON gu.perm_user_id=pu.perm_user_id JOIN tbl_perms_groups g ON g.group_id=gu.group_id';if($audience==='admins')$where.=" AND g.group_define_name='Site Admin'";elseif($audience==='lecturers')$where.=" AND (g.group_define_name='Lecturers' OR g.group_define_name LIKE '%^Lecturers')";elseif($audience==='students')$where.=" AND (g.group_define_name='Students' OR g.group_define_name LIKE '%^Students')";else return array();}$rows=$this->getArray("SELECT DISTINCT LOWER(TRIM(u.emailaddress)) AS emailaddress FROM tbl_users u{$join} WHERE {$where} ORDER BY emailaddress");$emails=array();foreach($rows as $row){$email=(string)($row['emailaddress']??'');if(filter_var($email,FILTER_VALIDATE_EMAIL)!==false)$emails[]=$email;}return array_values(array_unique($emails));}
    /** Return recent maintenance-email outbox rows with their latest attempt. */
    public function recentEmailDeliveries($limit=25){$limit=max(1,min(100,(int)$limit));return $this->getArray("SELECT o.id,o.recipient,o.subject,o.status,o.transport,o.attempts,o.sent_at,o.last_error,o.date_created,a.outcome AS attempt_outcome,a.response_code,a.provider_reference,a.error_detail,a.date_created AS attempt_date FROM tbl_communications_outbox o LEFT JOIN tbl_communications_attempts a ON a.id=(SELECT a2.id FROM tbl_communications_attempts a2 WHERE a2.outbox_id=o.id ORDER BY a2.attempt_number DESC LIMIT 1) WHERE o.channel='email' AND o.metadata_json LIKE '%\"purpose\":\"maintenance_notice\"%' ORDER BY o.date_created DESC LIMIT {$limit}");}
    /** Quote a scalar with the framework database adapter. */
    private function quoteValue($value){$db=$this->objEngine->getDbObj();return method_exists($db,'quoteSmart')?$db->quoteSmart((string)$value):"'".str_replace("'","''",(string)$value)."'";}
}
?>
