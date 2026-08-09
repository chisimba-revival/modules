<?php
$tablename = 'tbl_faq26_items';
$options = array('collate' => 'utf8_general_ci', 'character_set' => 'utf8');
$fields = array(
    'id'            => array('type' => 'text', 'length' => 32, 'notnull' => TRUE),
    'scope_type'    => array('type' => 'text', 'length' => 32, 'notnull' => TRUE),
    'scope_id'      => array('type' => 'text', 'length' => 64, 'notnull' => TRUE),
    'question'      => array('type' => 'text', 'length' => 255, 'notnull' => TRUE),
    'answer'        => array('type' => 'text', 'length' => 4000, 'notnull' => TRUE),
    'display_order' => array('type' => 'integer', 'length' => 4, 'notnull' => TRUE, 'default' => 0),
    'is_published'  => array('type' => 'integer', 'length' => 1, 'notnull' => TRUE, 'default' => 1),
    'creator_id'    => array('type' => 'text', 'length' => 32, 'notnull' => TRUE),
    'date_created'  => array('type' => 'timestamp', 'notnull' => TRUE),
    'date_modified' => array('type' => 'timestamp', 'notnull' => TRUE)
);
$tableIndexes = array(
    'faq26_primary'   => array('primary' => TRUE, 'fields' => array('id' => array())),
    'faq26_scope_idx' => array('fields' => array('scope_type' => array(), 'scope_id' => array()))
);
?>
