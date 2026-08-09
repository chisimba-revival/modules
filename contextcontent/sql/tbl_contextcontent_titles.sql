<?php

$tablename = 'tbl_contextcontent_titles';

// Options line for comments, encoding and character set
$options = array('collate' => 'utf8_general_ci', 'character_set' => 'utf8');

$fields = array(
    'id' => array(
        'type' => 'text',
        'length' => 32,
        'notnull' => TRUE
        ),
    'contenttype' => array(
        'type' => 'text',
        'length' => 64,
        'notnull' => TRUE,
        'default' => 'rich_text'
        ),
    'providermodule' => array(
        'type' => 'text',
        'length' => 64
        ),
    'provideritemid' => array(
        'type' => 'text',
        'length' => 64
        ),
    'creatorid' => array(
        'type' => 'text',
        'length' => 64,
        'notnull' => TRUE
        ),
    'datecreated' => array(
        'type' => 'timestamp',
        'notnull' => TRUE
        ),
    'modifierid' => array(
        'type' => 'text',
        'length' => 64
        ),
    'datemodified' => array(
        'type' => 'timestamp'
        )
    );

?>
