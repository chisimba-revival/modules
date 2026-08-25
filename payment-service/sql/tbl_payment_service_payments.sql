<?php
$tablename = 'tbl_payment_service_payments';
$options = array('comment' => 'Canonical provider-confirmed payments', 'collate' => 'utf8_general_ci', 'character_set' => 'utf8');
$fields = array(
    'id' => array('type' => 'text', 'length' => 32, 'notnull' => TRUE),
    'intent_id' => array('type' => 'text', 'length' => 32, 'notnull' => TRUE),
    'provider_code' => array('type' => 'text', 'length' => 32, 'notnull' => TRUE),
    'provider_payment_id' => array('type' => 'text', 'length' => 191, 'notnull' => TRUE),
    'state' => array('type' => 'text', 'length' => 32, 'notnull' => TRUE),
    'amount_minor' => array('type' => 'integer', 'notnull' => TRUE),
    'currency' => array('type' => 'text', 'length' => 3, 'notnull' => TRUE),
    'created_at' => array('type' => 'timestamp', 'notnull' => TRUE),
    'updated_at' => array('type' => 'timestamp', 'notnull' => TRUE)
);
$tableIndexes = array(
    'payment_primary' => array('primary' => TRUE, 'fields' => array('id' => array())),
    'payment_provider_id' => array('unique' => TRUE, 'fields' => array('provider_code' => array(), 'provider_payment_id' => array())),
    'payment_intent' => array('fields' => array('intent_id' => array()))
);
?>
