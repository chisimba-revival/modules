<?php
$tablename = 'tbl_payment_service_events';
$options = array('comment' => 'Verified idempotent provider event inbox', 'collate' => 'utf8_general_ci', 'character_set' => 'utf8');
$fields = array(
    'id' => array('type' => 'text', 'length' => 32, 'notnull' => TRUE),
    'provider_code' => array('type' => 'text', 'length' => 32, 'notnull' => TRUE),
    'provider_event_id' => array('type' => 'text', 'length' => 191, 'notnull' => TRUE),
    'intent_id' => array('type' => 'text', 'length' => 32, 'notnull' => TRUE),
    'event_type' => array('type' => 'text', 'length' => 64, 'notnull' => TRUE),
    'reason_code' => array('type' => 'text', 'length' => 96),
    'occurred_at' => array('type' => 'timestamp', 'notnull' => TRUE),
    'received_at' => array('type' => 'timestamp', 'notnull' => TRUE),
    'processed_at' => array('type' => 'timestamp'),
    'processing_result' => array('type' => 'text', 'length' => 64)
);
$tableIndexes = array(
    'payment_event_primary' => array('primary' => TRUE, 'fields' => array('id' => array())),
    'payment_event_provider_id' => array('unique' => TRUE, 'fields' => array('provider_code' => array(), 'provider_event_id' => array())),
    'payment_event_intent' => array('fields' => array('intent_id' => array(), 'occurred_at' => array()))
);
?>
