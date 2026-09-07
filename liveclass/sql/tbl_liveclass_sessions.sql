<?php
$tablename = 'tbl_liveclass_sessions';
$options = array('comment' => 'Provider-neutral live sessions', 'collate' => 'utf8_general_ci', 'character_set' => 'utf8');
$fields = array(
    'id' => array('type' => 'text', 'length' => 32, 'notnull' => TRUE),
    'session_type' => array('type' => 'text', 'length' => 16, 'notnull' => TRUE),
    'context_code' => array('type' => 'text', 'length' => 191),
    'name' => array('type' => 'text', 'length' => 255, 'notnull' => TRUE),
    'description' => array('type' => 'text'),
    'starts_at' => array('type' => 'timestamp', 'notnull' => TRUE),
    'duration_minutes' => array('type' => 'integer', 'notnull' => TRUE),
    'record_session' => array('type' => 'integer', 'length' => 1, 'default' => 0),
    'provider_code' => array('type' => 'text', 'length' => 32, 'notnull' => TRUE),
    'meeting_url' => array('type' => 'text', 'length' => 2048),
    'provider_meeting_id' => array('type' => 'text', 'length' => 191, 'notnull' => TRUE),
    'status' => array('type' => 'text', 'length' => 16, 'notnull' => TRUE),
    'created_by' => array('type' => 'text', 'length' => 25, 'notnull' => TRUE),
    'created_at' => array('type' => 'timestamp', 'notnull' => TRUE),
    'updated_at' => array('type' => 'timestamp', 'notnull' => TRUE)
);
$tableIndexes = array(
    'liveclass_primary' => array('primary' => TRUE, 'fields' => array('id' => array())),
    'liveclass_meeting' => array('unique' => TRUE, 'fields' => array('provider_meeting_id' => array())),
    'liveclass_context_start' => array('fields' => array('context_code' => array(), 'starts_at' => array()))
);
?>
