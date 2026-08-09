<?php
$tablename = 'tbl_contextcontent_bookmarks';
$options = array('collate' => 'utf8_general_ci', 'character_set' => 'utf8');
$fields = array(
    'id' => array('type' => 'text', 'length' => 32, 'notnull' => TRUE),
    'contextcode' => array('type' => 'text', 'length' => 255, 'notnull' => TRUE),
    'placementid' => array('type' => 'text', 'length' => 32, 'notnull' => TRUE),
    'userid' => array('type' => 'text', 'length' => 64, 'notnull' => TRUE),
    'datecreated' => array('type' => 'timestamp', 'notnull' => TRUE)
);
$name = 'tbl_contextcontent_bookmarks_idx';
$indexes = array('fields' => array(
    'contextcode' => array(), 'placementid' => array(), 'userid' => array()
));
?>
