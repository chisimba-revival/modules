<?php
/** Notification event schema. @author Derek Keats @package notifications */
$tablename='tbl_notification_events';
$options=array('comment'=>'Immutable notification events','collate'=>'utf8mb4_unicode_ci','charset'=>'utf8mb4');
$fields=array(
 'id'=>array('type'=>'text','length'=>32,'notnull'=>TRUE),
 'idempotency_key'=>array('type'=>'text','length'=>191,'notnull'=>TRUE),
 'event_type'=>array('type'=>'text','length'=>128,'notnull'=>TRUE),
 'actor_user_id'=>array('type'=>'text','length'=>64),
 'context_code'=>array('type'=>'text','length'=>64),
 'source_type'=>array('type'=>'text','length'=>64,'notnull'=>TRUE),
 'source_id'=>array('type'=>'text','length'=>191,'notnull'=>TRUE),
 'title'=>array('type'=>'text','length'=>255,'notnull'=>TRUE),
 'summary'=>array('type'=>'clob','notnull'=>TRUE),
 'target_url'=>array('type'=>'text','length'=>2048),
 'payload_json'=>array('type'=>'clob'),
 'datecreated'=>array('type'=>'timestamp','notnull'=>TRUE)
);
$tableIndexes=array(
 'notification_events_primary'=>array('primary'=>TRUE,'fields'=>array('id'=>array())),
 'notification_events_idempotency'=>array('unique'=>TRUE,'fields'=>array('idempotency_key'=>array())),
 'notification_events_context'=>array('fields'=>array('context_code'=>array(),'datecreated'=>array()))
);
?>
