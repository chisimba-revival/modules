<?php
$tablename = 'tbl_kanban_access';
$options = array('comment' => 'Extensible Kanban board access grants', 'collate' => 'utf8_general_ci', 'character_set' => 'utf8');
$fields = array(
    'id' => array('type' => 'text', 'length' => 32, 'notnull' => TRUE),
    'boardid' => array('type' => 'text', 'length' => 32, 'notnull' => TRUE),
    'principaltype' => array('type' => 'text', 'length' => 24, 'notnull' => TRUE),
    'principalid' => array('type' => 'text', 'length' => 255, 'notnull' => TRUE),
    'permission' => array('type' => 'text', 'length' => 16, 'notnull' => TRUE),
    'createdby' => array('type' => 'text', 'length' => 64, 'notnull' => TRUE),
    'datecreated' => array('type' => 'timestamp', 'notnull' => TRUE)
);
$tableIndexes = array(
    'kanban_access_primary' => array('primary' => TRUE, 'fields' => array('id' => array())),
    'kanban_access_grant' => array('unique' => TRUE, 'fields' => array('boardid' => array(), 'principaltype' => array(), 'principalid' => array())),
    'kanban_access_principal' => array('fields' => array('principaltype' => array(), 'principalid' => array(), 'permission' => array()))
);
?>
