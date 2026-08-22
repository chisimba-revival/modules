<?php
$tablename = 'tbl_ingestservice_runs';
$options = array('collate' => 'utf8_general_ci', 'character_set' => 'utf8');
$fields = array(
    'id' => array('type' => 'text', 'length' => 32, 'notnull' => TRUE),
    'sourcefingerprint' => array('type' => 'text', 'length' => 64, 'notnull' => TRUE),
    'consumer' => array('type' => 'text', 'length' => 64, 'notnull' => TRUE),
    'consumertarget' => array('type' => 'text', 'length' => 255, 'notnull' => TRUE),
    'status' => array('type' => 'text', 'length' => 16, 'notnull' => TRUE),
    'resultreference' => array('type' => 'clob'),
    'creatorid' => array('type' => 'text', 'length' => 50),
    'datecreated' => array('type' => 'timestamp', 'notnull' => TRUE),
    'datecompleted' => array('type' => 'timestamp')
);
$tableIndexes = array(
    'ingestservice_runs_primary' => array('primary' => TRUE, 'fields' => array('id' => array())),
    'ingestservice_runs_source_consumer_target' => array(
        'unique' => TRUE,
        'fields' => array('sourcefingerprint' => array(), 'consumer' => array(), 'consumertarget' => array())
    )
);
?>
