<?php
$tablename = 'tbl_payment_service_tier_content';
$options = array('comment' => 'Administrator-editable membership tier presentation', 'collate' => 'utf8_general_ci', 'character_set' => 'utf8');
$fields = array(
    'id' => array('type' => 'text', 'length' => 32, 'notnull' => TRUE),
    'tier_code' => array('type' => 'text', 'length' => 16, 'notnull' => TRUE),
    'summary' => array('type' => 'text', 'length' => 500, 'notnull' => TRUE),
    'features' => array('type' => 'clob', 'notnull' => TRUE),
    'created_at' => array('type' => 'timestamp', 'notnull' => TRUE),
    'updated_at' => array('type' => 'timestamp', 'notnull' => TRUE)
);
$tableIndexes = array(
    'payment_tier_content_primary' => array('primary' => TRUE, 'fields' => array('id' => array())),
    'payment_tier_content_code' => array('unique' => TRUE, 'fields' => array('tier_code' => array()))
);
?>
