<?php
/** Per-recipient notification state schema. @author Derek Keats @package notifications */
$tablename='tbl_notification_recipients';
$options=array('comment'=>'Per-recipient notification state','collate'=>'utf8mb4_unicode_ci','charset'=>'utf8mb4');
$fields=array(
 'id'=>array('type'=>'text','length'=>32,'notnull'=>TRUE),
 'event_id'=>array('type'=>'text','length'=>32,'notnull'=>TRUE),
 'user_id'=>array('type'=>'text','length'=>64,'notnull'=>TRUE),
 'state'=>array('type'=>'text','length'=>16,'notnull'=>TRUE,'default'=>'unread'),
 'read_at'=>array('type'=>'timestamp'),
 'archived_at'=>array('type'=>'timestamp'),
 'datecreated'=>array('type'=>'timestamp','notnull'=>TRUE),
 'datemodified'=>array('type'=>'timestamp','notnull'=>TRUE)
);
$tableIndexes=array(
 'notification_recipients_primary'=>array('primary'=>TRUE,'fields'=>array('id'=>array())),
 'notification_recipient_once'=>array('unique'=>TRUE,'fields'=>array('event_id'=>array(),'user_id'=>array())),
 'notification_recipient_feed'=>array('fields'=>array('user_id'=>array(),'state'=>array(),'datecreated'=>array()))
);
?>
