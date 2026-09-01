<?php
// Linked activities remain owned by their provider modules; this table stores
// only the Gradebook plan, weighting, optional dates and result policy.
$tablename = 'tbl_gradebook_assessment_plan_items';
$options = array('comment' => 'Weighted assessment items owned by Gradebook', 'collate' => 'utf8_general_ci', 'character_set' => 'utf8');
$fields = array(
    'id' => array('type' => 'text', 'length' => 32, 'notnull' => TRUE),
    'plan_id' => array('type' => 'text', 'length' => 32, 'notnull' => TRUE),
    'provider_key' => array('type' => 'text', 'length' => 64, 'notnull' => TRUE),
    'provider_module' => array('type' => 'text', 'length' => 64, 'notnull' => TRUE),
    'activity_id' => array('type' => 'text', 'length' => 64, 'notnull' => TRUE),
    'name' => array('type' => 'text', 'length' => 255, 'notnull' => TRUE),
    'short_name' => array('type' => 'text', 'length' => 16, 'notnull' => TRUE, 'default' => ''),
    'sort_order' => array('type' => 'integer', 'length' => 11, 'notnull' => TRUE, 'default' => 0),
    'weight' => array('type' => 'decimal', 'length' => 12, 'scale' => 3, 'notnull' => TRUE, 'default' => 0),
    'include_in_course_mark' => array('type' => 'text', 'length' => 1, 'notnull' => TRUE, 'default' => 'Y'),
    'required_for_completion' => array('type' => 'text', 'length' => 1, 'notnull' => TRUE, 'default' => 'N'),
    'result_rule' => array('type' => 'text', 'length' => 64, 'notnull' => TRUE, 'default' => 'latest_completed'),
    'opening_enabled' => array('type' => 'text', 'length' => 1, 'notnull' => TRUE, 'default' => 'N'),
    'opening_date' => array('type' => 'timestamp'),
    'closing_enabled' => array('type' => 'text', 'length' => 1, 'notnull' => TRUE, 'default' => 'N'),
    'closing_date' => array('type' => 'timestamp'),
    'status' => array('type' => 'text', 'length' => 24, 'notnull' => TRUE, 'default' => 'active'),
    'created_by' => array('type' => 'text', 'length' => 255, 'notnull' => TRUE),
    'date_created' => array('type' => 'timestamp', 'notnull' => TRUE),
    'date_updated' => array('type' => 'timestamp', 'notnull' => TRUE)
);
$tableIndexes = array(
    'gradebook_assessment_plan_items_primary' => array('primary' => TRUE, 'fields' => array('id' => array())),
    'gradebook_assessment_plan_items_plan' => array('fields' => array('plan_id' => array(), 'sort_order' => array())),
    'gradebook_assessment_plan_items_activity' => array('unique' => TRUE, 'fields' => array('plan_id' => array(), 'provider_key' => array(), 'activity_id' => array()))
);
?>
