<?php
/**
 * Database definition for scoped Active Knowledge Maps.
 *
 * @author Derek Keats
 * @package knowledgemap
 */
$tablename = 'tbl_knowledgemap_maps';
$options = array('comment' => 'Scoped Active Knowledge Maps', 'collate' => 'utf8mb4_unicode_ci', 'character_set' => 'utf8mb4');
$fields = array(
    'id' => array('type' => 'text', 'length' => 32, 'notnull' => TRUE),
    'scopetype' => array('type' => 'text', 'length' => 16, 'notnull' => TRUE),
    'scopeid' => array('type' => 'text', 'length' => 255, 'notnull' => TRUE),
    'ownerid' => array('type' => 'text', 'length' => 64, 'notnull' => TRUE),
    'rootnodeid' => array('type' => 'text', 'length' => 64),
    'title' => array('type' => 'text', 'length' => 255, 'notnull' => TRUE),
    'description' => array('type' => 'text'),
    'revision' => array('type' => 'integer', 'length' => 10, 'default' => 1, 'notnull' => TRUE),
    'sourceformat' => array('type' => 'text', 'length' => 64),
    'sourcefingerprint' => array('type' => 'text', 'length' => 64),
    'sourcemetadata' => array('type' => 'clob'),
    'datecreated' => array('type' => 'timestamp', 'notnull' => TRUE),
    'datemodified' => array('type' => 'timestamp', 'notnull' => TRUE)
);
$tableIndexes = array(
    'knowledgemap_maps_primary' => array('primary' => TRUE, 'fields' => array('id' => array())),
    'knowledgemap_maps_scope' => array('fields' => array('scopetype' => array(), 'scopeid' => array())),
    'knowledgemap_maps_owner' => array('fields' => array('ownerid' => array())),
    'knowledgemap_maps_source' => array('fields' => array('sourcefingerprint' => array()))
);
?>
