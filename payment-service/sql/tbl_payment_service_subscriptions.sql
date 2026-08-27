<?php
$tablename = 'tbl_payment_service_subscriptions';
$options = array('comment' => 'Provider-neutral recurring membership mappings', 'collate' => 'utf8_general_ci', 'character_set' => 'utf8');
$fields = array(
    'id' => array('type' => 'text', 'length' => 32, 'notnull' => TRUE),
    'provider_code' => array('type' => 'text', 'length' => 32, 'notnull' => TRUE),
    'provider_subscription_id' => array('type' => 'text', 'length' => 191),
    'provider_customer_id' => array('type' => 'text', 'length' => 191, 'notnull' => TRUE),
    'provider_plan_id' => array('type' => 'text', 'length' => 191, 'notnull' => TRUE),
    'base_intent_id' => array('type' => 'text', 'length' => 32, 'notnull' => TRUE),
    'product_code' => array('type' => 'text', 'length' => 96, 'notnull' => TRUE),
    'state' => array('type' => 'text', 'length' => 32, 'notnull' => TRUE),
    'created_at' => array('type' => 'timestamp', 'notnull' => TRUE),
    'updated_at' => array('type' => 'timestamp', 'notnull' => TRUE)
);
$tableIndexes = array(
    'payment_subscription_primary' => array('primary' => TRUE, 'fields' => array('id' => array())),
    'payment_subscription_remote' => array('unique' => TRUE, 'fields' => array('provider_code' => array(), 'provider_customer_id' => array(), 'provider_plan_id' => array())),
    'payment_subscription_code' => array('fields' => array('provider_code' => array(), 'provider_subscription_id' => array())),
    'payment_subscription_intent' => array('fields' => array('base_intent_id' => array()))
);
?>
