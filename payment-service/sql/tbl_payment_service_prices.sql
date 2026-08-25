<?php
$tablename = 'tbl_payment_service_prices';
$options = array('comment' => 'Immutable effective-dated product price versions', 'collate' => 'utf8_general_ci', 'character_set' => 'utf8');
$fields = array(
    'id' => array('type' => 'text', 'length' => 32, 'notnull' => TRUE),
    'product_id' => array('type' => 'text', 'length' => 32, 'notnull' => TRUE),
    'version_code' => array('type' => 'text', 'length' => 64, 'notnull' => TRUE),
    'amount_minor' => array('type' => 'integer', 'notnull' => TRUE),
    'currency' => array('type' => 'text', 'length' => 3, 'notnull' => TRUE),
    'effective_from' => array('type' => 'timestamp', 'notnull' => TRUE),
    'effective_until' => array('type' => 'timestamp'),
    'created_at' => array('type' => 'timestamp', 'notnull' => TRUE)
);
$tableIndexes = array(
    'payment_price_primary' => array('primary' => TRUE, 'fields' => array('id' => array())),
    'payment_price_version' => array('unique' => TRUE, 'fields' => array('product_id' => array(), 'version_code' => array())),
    'payment_price_effective' => array('fields' => array('product_id' => array(), 'effective_from' => array()))
);
?>
