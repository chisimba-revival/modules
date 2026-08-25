<?php
$tablename = 'tbl_payment_service_products';
$options = array('comment' => 'Sellable membership and private-course products', 'collate' => 'utf8_general_ci', 'character_set' => 'utf8');
$fields = array(
    'id' => array('type' => 'text', 'length' => 32, 'notnull' => TRUE),
    'code' => array('type' => 'text', 'length' => 96, 'notnull' => TRUE),
    'name' => array('type' => 'text', 'length' => 191, 'notnull' => TRUE),
    'purpose_type' => array('type' => 'text', 'length' => 32, 'notnull' => TRUE),
    'purpose_id' => array('type' => 'text', 'length' => 191, 'notnull' => TRUE),
    'billing_period' => array('type' => 'text', 'length' => 32, 'notnull' => TRUE),
    'duration_months' => array('type' => 'integer'),
    'active' => array('type' => 'boolean', 'notnull' => TRUE, 'default' => 1),
    'created_at' => array('type' => 'timestamp', 'notnull' => TRUE),
    'updated_at' => array('type' => 'timestamp', 'notnull' => TRUE)
);
$tableIndexes = array(
    'payment_product_primary' => array('primary' => TRUE, 'fields' => array('id' => array())),
    'payment_product_code' => array('unique' => TRUE, 'fields' => array('code' => array())),
    'payment_product_purpose' => array('fields' => array('purpose_type' => array(), 'purpose_id' => array()))
);
?>
