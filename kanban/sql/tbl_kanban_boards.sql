<?php
$tablename = 'tbl_kanban_boards';
$options = array('comment' => 'Scoped Kanban boards', 'collate' => 'utf8_general_ci', 'character_set' => 'utf8');
$fields = array(
    'id' => array('type' => 'text', 'length' => 32, 'notnull' => TRUE),
    'scopetype' => array('type' => 'text', 'length' => 16, 'notnull' => TRUE),
    'scopeid' => array('type' => 'text', 'length' => 255, 'notnull' => TRUE),
    'ownerid' => array('type' => 'text', 'length' => 64, 'notnull' => TRUE),
    'title' => array('type' => 'text', 'length' => 255, 'notnull' => TRUE),
    'description' => array('type' => 'text'),
    'isarchived' => array('type' => 'integer', 'length' => 1, 'default' => 0, 'notnull' => TRUE),
    'sortorder' => array('type' => 'integer', 'length' => 10, 'default' => 0, 'notnull' => TRUE),
    'datecreated' => array('type' => 'timestamp', 'notnull' => TRUE),
    'datemodified' => array('type' => 'timestamp', 'notnull' => TRUE)
);
$tableIndexes = array(
    'kanban_boards_primary' => array('primary' => TRUE, 'fields' => array('id' => array())),
    'kanban_boards_scope' => array('fields' => array('scopetype' => array(), 'scopeid' => array(), 'isarchived' => array(), 'sortorder' => array())),
    'kanban_boards_owner' => array('fields' => array('ownerid' => array()))
);
?>
