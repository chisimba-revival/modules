<?php
$tablename = 'tbl_kanban_subtasks';
$options = array('comment' => 'Kanban subtasks', 'collate' => 'utf8_general_ci', 'character_set' => 'utf8');
$fields = array(
    'id' => array('type' => 'text', 'length' => 32, 'notnull' => TRUE),
    'taskid' => array('type' => 'text', 'length' => 32, 'notnull' => TRUE),
    'title' => array('type' => 'text', 'notnull' => TRUE),
    'iscompleted' => array('type' => 'integer', 'length' => 1, 'default' => 0, 'notnull' => TRUE),
    'sortorder' => array('type' => 'integer', 'length' => 10, 'default' => 0, 'notnull' => TRUE),
    'datecreated' => array('type' => 'timestamp', 'notnull' => TRUE),
    'datemodified' => array('type' => 'timestamp', 'notnull' => TRUE)
);
$tableIndexes = array(
    'kanban_subtasks_primary' => array('primary' => TRUE, 'fields' => array('id' => array())),
    'kanban_subtasks_task' => array('fields' => array('taskid' => array(), 'sortorder' => array()))
);
?>
