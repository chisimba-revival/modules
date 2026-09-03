<?php
/** Persistence for immutable notification events. @author Derek Keats @package notifications */
if(empty($GLOBALS['kewl_entry_point_run']))die('You cannot view this page directly');
class dbnotificationevents extends dbTable
{
 /** Bind the event table. */
 public function init($tableName=null,$pearDb=null,$errorCallback='globalPearErrorCallback'){parent::init('tbl_notification_events');}
 /** Find an event by producer-supplied idempotency key. */
 public function byKey($key){$row=$this->getRow('idempotency_key',(string)$key);return is_array($row)&&!empty($row['id'])?$row:null;}
 /** Store one immutable event. */
 public function createEvent(array $values){return $this->insert($values);}
}
?>
