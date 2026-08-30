<?php
$tablename = 'tbl_kanban_tasks';
$options = array('comment' => 'Kanban tasks', 'collate' => 'utf8_general_ci', 'character_set' => 'utf8');
$fields = array(
    'id' => array('type' => 'text', 'length' => 32, 'notnull' => TRUE),
    'boardid' => array('type' => 'text', 'length' => 32, 'notnull' => TRUE),
    'title' => array('type' => 'text', 'length' => 255, 'notnull' => TRUE),
    'description' => array('type' => 'text'),
    'notes' => array('type' => 'text'),
    'status' => array('type' => 'text', 'length' => 24, 'notnull' => TRUE),
    'sortorder' => array('type' => 'integer', 'length' => 10, 'default' => 0, 'notnull' => TRUE),
    'datecreated' => array('type' => 'timestamp', 'notnull' => TRUE),
    'datemodified' => array('type' => 'timestamp', 'notnull' => TRUE)
);
$tableIndexes = array(
    'kanban_tasks_primary' => array('primary' => TRUE, 'fields' => array('id' => array())),
    'kanban_tasks_board_status' => array('fields' => array('boardid' => array(), 'status' => array(), 'sortorder' => array()))
);
?>
