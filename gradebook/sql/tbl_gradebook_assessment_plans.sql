<?php
// Gradebook is the sole owner of course assessment planning.
$tablename = 'tbl_gradebook_assessment_plans';
$options = array('comment' => 'Course assessment plans owned by Gradebook', 'collate' => 'utf8_general_ci', 'character_set' => 'utf8');
$fields = array(
    'id' => array('type' => 'text', 'length' => 32, 'notnull' => TRUE),
    'context_code' => array('type' => 'text', 'length' => 255, 'notnull' => TRUE),
    'status' => array('type' => 'text', 'length' => 24, 'notnull' => TRUE, 'default' => 'draft'),
    'published_by' => array('type' => 'text', 'length' => 255),
    'date_published' => array('type' => 'timestamp'),
    'created_by' => array('type' => 'text', 'length' => 255, 'notnull' => TRUE),
    'date_created' => array('type' => 'timestamp', 'notnull' => TRUE),
    'date_updated' => array('type' => 'timestamp', 'notnull' => TRUE)
);
$tableIndexes = array(
    'gradebook_assessment_plans_primary' => array('primary' => TRUE, 'fields' => array('id' => array())),
    'gradebook_assessment_plans_context' => array('unique' => TRUE, 'fields' => array('context_code' => array()))
);
?>
