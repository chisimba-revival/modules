<?php
$tablename = 'tbl_payment_service_provider_plans';
$options = array('comment' => 'Provider plan mappings for recurring products', 'collate' => 'utf8_general_ci', 'character_set' => 'utf8');
$fields = array(
    'id' => array('type' => 'text', 'length' => 32, 'notnull' => TRUE),
    'provider_code' => array('type' => 'text', 'length' => 32, 'notnull' => TRUE),
    'product_code' => array('type' => 'text', 'length' => 96, 'notnull' => TRUE),
    'price_version' => array('type' => 'text', 'length' => 64, 'notnull' => TRUE),
    'provider_plan_id' => array('type' => 'text', 'length' => 191, 'notnull' => TRUE),
    'created_at' => array('type' => 'timestamp', 'notnull' => TRUE),
    'updated_at' => array('type' => 'timestamp', 'notnull' => TRUE)
);
$tableIndexes = array(
    'payment_provider_plan_primary' => array('primary' => TRUE, 'fields' => array('id' => array())),
    'payment_provider_plan_version' => array('unique' => TRUE, 'fields' => array('provider_code' => array(), 'product_code' => array(), 'price_version' => array())),
    'payment_provider_plan_remote' => array('fields' => array('provider_code' => array(), 'provider_plan_id' => array()))
);
?>
