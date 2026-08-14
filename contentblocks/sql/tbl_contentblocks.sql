<?php
$tablename = 'tbl_contentblocks';
$options = array(
    'comment' => 'Reusable site and course content blocks',
    'collate' => 'utf8_general_ci',
    'character_set' => 'utf8',
);
$fields = array(
    'id' => array('type' => 'text', 'length' => 32, 'notnull' => 1),
    'blockkey' => array('type' => 'text', 'length' => 100, 'notnull' => 1),
    'scope' => array('type' => 'text', 'length' => 10, 'notnull' => 1),
    'contextcode' => array('type' => 'text', 'length' => 100),
    'blocktype' => array('type' => 'text', 'length' => 20, 'notnull' => 1),
    'blockwidth' => array('type' => 'text', 'length' => 10, 'notnull' => 1),
    'title' => array('type' => 'text', 'length' => 250, 'notnull' => 1),
    'body_html' => array('type' => 'clob'),
    'image_url' => array('type' => 'text', 'length' => 1000),
    'action_label' => array('type' => 'text', 'length' => 250),
    'action_url' => array('type' => 'text', 'length' => 1000),
    'show_title' => array('type' => 'text', 'length' => 1, 'notnull' => 1, 'default' => '1'),
    'creatorid' => array('type' => 'text', 'length' => 32),
    'modifierid' => array('type' => 'text', 'length' => 32),
    'datecreated' => array('type' => 'timestamp'),
    'datemodified' => array('type' => 'timestamp'),
    'deleted' => array('type' => 'text', 'length' => 1, 'notnull' => 1, 'default' => '0'),
);
?>
