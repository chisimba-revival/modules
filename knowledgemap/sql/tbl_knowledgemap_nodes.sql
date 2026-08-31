<?php
/**
 * Database definition for knowledge-map nodes.
 *
 * @author Derek Keats
 * @package knowledgemap
 */
$tablename = 'tbl_knowledgemap_nodes';
$options = array('comment' => 'Active Knowledge Map nodes', 'collate' => 'utf8_general_ci', 'character_set' => 'utf8');
$fields = array(
    'id' => array('type' => 'text', 'length' => 64, 'notnull' => TRUE),
    'mapid' => array('type' => 'text', 'length' => 32, 'notnull' => TRUE),
    'nodetype' => array('type' => 'text', 'length' => 32, 'default' => 'standard', 'notnull' => TRUE),
    'title' => array('type' => 'text', 'length' => 255, 'notnull' => TRUE),
    'description' => array('type' => 'clob'),
    'presentation' => array('type' => 'clob'),
    'sortorder' => array('type' => 'integer', 'length' => 10, 'default' => 0, 'notnull' => TRUE),
    'datecreated' => array('type' => 'timestamp', 'notnull' => TRUE),
    'datemodified' => array('type' => 'timestamp', 'notnull' => TRUE)
);
$tableIndexes = array(
    'knowledgemap_nodes_primary' => array('primary' => TRUE, 'fields' => array('mapid' => array(), 'id' => array())),
    'knowledgemap_nodes_map' => array('fields' => array('mapid' => array(), 'sortorder' => array()))
);
?>
