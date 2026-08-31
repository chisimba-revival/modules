<?php
/**
 * Database definition for typed knowledge-map relationships.
 *
 * @author Derek Keats
 * @package knowledgemap
 */
$tablename = 'tbl_knowledgemap_relationships';
$options = array('comment' => 'Typed Active Knowledge Map relationships', 'collate' => 'utf8mb4_unicode_ci', 'character_set' => 'utf8mb4');
$fields = array(
    'id' => array('type' => 'text', 'length' => 64, 'notnull' => TRUE),
    'mapid' => array('type' => 'text', 'length' => 32, 'notnull' => TRUE),
    'relationshiptype' => array('type' => 'text', 'length' => 64, 'notnull' => TRUE),
    'fromnodeid' => array('type' => 'text', 'length' => 64, 'notnull' => TRUE),
    'tonodeid' => array('type' => 'text', 'length' => 64),
    'externaltarget' => array('type' => 'clob'),
    'properties' => array('type' => 'clob'),
    'sortorder' => array('type' => 'integer', 'length' => 10, 'default' => 0, 'notnull' => TRUE),
    'datecreated' => array('type' => 'timestamp', 'notnull' => TRUE)
);
$tableIndexes = array(
    'knowledgemap_relationships_primary' => array('primary' => TRUE, 'fields' => array('mapid' => array(), 'id' => array())),
    'knowledgemap_relationships_outgoing' => array('fields' => array('mapid' => array(), 'fromnodeid' => array(), 'relationshiptype' => array())),
    'knowledgemap_relationships_incoming' => array('fields' => array('mapid' => array(), 'tonodeid' => array(), 'relationshiptype' => array()))
);
?>
