<?php
$tablename = 'tbl_sitepages';
$options = array('comment' => 'Editable public information and policy pages','collate' => 'utf8_general_ci','character_set' => 'utf8');
$fields = array(
    'id' => array('type' => 'text','length' => 32,'notnull' => 1),
    'slug' => array('type' => 'text','length' => 190,'notnull' => 1),
    'title' => array('type' => 'text','length' => 250,'notnull' => 1),
    'body_html' => array('type' => 'clob','notnull' => 1),
    'status' => array('type' => 'text','length' => 20,'notnull' => 1,'default' => 'draft'),
    'creatorid' => array('type' => 'text','length' => 32),
    'modifierid' => array('type' => 'text','length' => 32),
    'datecreated' => array('type' => 'timestamp'),
    'datemodified' => array('type' => 'timestamp'),
);
$indexes = array(
    'slug' => array('fields' => array('slug' => array()),'unique' => true),
    'status' => array('fields' => array('status' => array())),
);
?>
