<?php
/**
 * Database definition for invited-user and future principal grants.
 *
 * @author Derek Keats
 * @package knowledgemap
 */
$tablename = 'tbl_knowledgemap_access';
$options = array('comment' => 'Active Knowledge Map access grants', 'collate' => 'utf8mb4_unicode_ci', 'character_set' => 'utf8mb4');
$fields = array(
    'id' => array('type' => 'text', 'length' => 32, 'notnull' => TRUE),
    'mapid' => array('type' => 'text', 'length' => 32, 'notnull' => TRUE),
    'principaltype' => array('type' => 'text', 'length' => 24, 'notnull' => TRUE),
    'principalid' => array('type' => 'text', 'length' => 255, 'notnull' => TRUE),
    'permission' => array('type' => 'text', 'length' => 16, 'notnull' => TRUE),
    'createdby' => array('type' => 'text', 'length' => 64, 'notnull' => TRUE),
    'datecreated' => array('type' => 'timestamp', 'notnull' => TRUE)
);
$tableIndexes = array(
    'knowledgemap_access_primary' => array('primary' => TRUE, 'fields' => array('id' => array())),
    'knowledgemap_access_grant' => array('unique' => TRUE, 'fields' => array('mapid' => array(), 'principaltype' => array(), 'principalid' => array())),
    'knowledgemap_access_principal' => array('fields' => array('principaltype' => array(), 'principalid' => array(), 'permission' => array()))
);
?>
