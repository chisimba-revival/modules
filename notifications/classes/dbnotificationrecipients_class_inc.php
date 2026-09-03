<?php
/** Persistence and feed queries for notification recipients. @author Derek Keats @package notifications */
if(empty($GLOBALS['kewl_entry_point_run']))die('You cannot view this page directly');
class dbnotificationrecipients extends dbTable
{
 /** Bind the recipient-state table. */
 public function init($tableName=null,$pearDb=null,$errorCallback='globalPearErrorCallback'){parent::init('tbl_notification_recipients');}
 /** Add one recipient, relying on the unique event/user boundary. */
 public function addRecipient(array $values){return $this->insert($values);}
 /** Return a newest-first page owned by one user. */
 public function feed($userId,$limit,$cursor=null,$unreadOnly=false){$q=$this->quoteValue($userId);$where="r.user_id={$q} AND r.archived_at IS NULL";if($unreadOnly)$where.=" AND r.state='unread'";if(is_array($cursor)){$date=$this->quoteValue($cursor['date']);$id=$this->quoteValue($cursor['id']);$where.=" AND (r.datecreated<{$date} OR (r.datecreated={$date} AND r.id<{$id}))";}$limit=max(1,min(100,(int)$limit));return $this->getArray("SELECT r.id AS notification_id,r.state,r.read_at,r.datecreated,e.id AS event_id,e.event_type,e.actor_user_id,e.context_code,e.source_type,e.source_id,e.title,e.summary,e.target_url,e.payload_json FROM tbl_notification_recipients r JOIN tbl_notification_events e ON e.id=r.event_id WHERE {$where} ORDER BY r.datecreated DESC,r.id DESC LIMIT ".($limit+1));}
 /** Count unread notifications owned by one user. */
 public function unreadCount($userId){$q=$this->quoteValue($userId);$rows=$this->getArray("SELECT COUNT(*) AS total FROM tbl_notification_recipients WHERE user_id={$q} AND state='unread' AND archived_at IS NULL");return (int)($rows[0]['total']??0);}
 /** Mark one owned notification read without permitting cross-user mutation. */
 public function markRead($id,$userId,$now){$qi=$this->quoteValue($id);$qu=$this->quoteValue($userId);return $this->query("UPDATE tbl_notification_recipients SET state='read',read_at=COALESCE(read_at,".$this->quoteValue($now)."),datemodified=".$this->quoteValue($now)." WHERE id={$qi} AND user_id={$qu} AND archived_at IS NULL");}
 /** Quote a scalar using the active database adapter. */
 private function quoteValue($value){$db=$this->objEngine->getDbObj();return method_exists($db,'quoteSmart')?$db->quoteSmart((string)$value):"'".str_replace("'","''",(string)$value)."'";}
}
?>
