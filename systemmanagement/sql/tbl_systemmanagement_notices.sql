<?php
/** Database definition for scheduled system notices. @author Derek Keats @package systemmanagement */
$tablename = 'tbl_systemmanagement_notices';
$options = array('comment' => 'Scheduled system notices by audience', 'collate' => 'utf8mb4_unicode_ci', 'charset' => 'utf8mb4');
$fields = array(
    'id' => array('type' => 'text', 'length' => 32, 'notnull' => TRUE),
    'title' => array('type' => 'text', 'length' => 255, 'notnull' => TRUE),
    'message' => array('type' => 'clob', 'notnull' => TRUE),
    'audience' => array('type' => 'text', 'length' => 16, 'notnull' => TRUE),
    'starts_at' => array('type' => 'timestamp'),
    'ends_at' => array('type' => 'timestamp'),
    'created_by' => array('type' => 'text', 'length' => 64, 'notnull' => TRUE),
    'datecreated' => array('type' => 'timestamp', 'notnull' => TRUE),
    'datemodified' => array('type' => 'timestamp', 'notnull' => TRUE)
);
$tableIndexes = array(
    'systemmanagement_notices_primary' => array('primary' => TRUE, 'fields' => array('id' => array())),
    'systemmanagement_notices_schedule' => array('fields' => array('starts_at' => array(), 'ends_at' => array())),
    'systemmanagement_notices_audience' => array('fields' => array('audience' => array()))
);
?>
