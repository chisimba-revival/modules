<?php
$tablename = 'tbl_payment_service_intents';
$options = array('comment' => 'Provider-neutral immutable checkout intents', 'collate' => 'utf8_general_ci', 'character_set' => 'utf8');
$fields = array(
    'id' => array('type' => 'text', 'length' => 32, 'notnull' => TRUE),
    'user_id' => array('type' => 'text', 'length' => 25, 'notnull' => TRUE),
    'purpose_type' => array('type' => 'text', 'length' => 32, 'notnull' => TRUE),
    'purpose_id' => array('type' => 'text', 'length' => 191, 'notnull' => TRUE),
    'product_code' => array('type' => 'text', 'length' => 96, 'notnull' => TRUE),
    'price_version' => array('type' => 'text', 'length' => 64, 'notnull' => TRUE),
    'amount_minor' => array('type' => 'integer', 'notnull' => TRUE),
    'currency' => array('type' => 'text', 'length' => 3, 'notnull' => TRUE),
    'provider_code' => array('type' => 'text', 'length' => 32, 'notnull' => TRUE),
    'state' => array('type' => 'text', 'length' => 32, 'notnull' => TRUE),
    'provider_reference' => array('type' => 'text', 'length' => 191),
    'failure_code' => array('type' => 'text', 'length' => 96),
    'idempotency_key' => array('type' => 'text', 'length' => 191, 'notnull' => TRUE),
    'correlation_id' => array('type' => 'text', 'length' => 64, 'notnull' => TRUE),
    'created_at' => array('type' => 'timestamp', 'notnull' => TRUE),
    'updated_at' => array('type' => 'timestamp', 'notnull' => TRUE)
);
$tableIndexes = array(
    'payment_intent_primary' => array('primary' => TRUE, 'fields' => array('id' => array())),
    'payment_intent_idempotency' => array('unique' => TRUE, 'fields' => array('idempotency_key' => array())),
    'payment_intent_user' => array('fields' => array('user_id' => array(), 'created_at' => array()))
);
?>
