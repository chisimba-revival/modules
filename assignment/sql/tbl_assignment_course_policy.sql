<?php
$tablename = 'tbl_assignment_course_policy';
$options = array('comment' => 'Course-level Assignment submission policy', 'collate' => 'utf8_general_ci', 'character_set' => 'utf8');
$fields = array(
    'id' => array('type' => 'text', 'length' => 32, 'notnull' => 1, 'default' => ''),
    'contextcode' => array('type' => 'text', 'length' => 255, 'notnull' => 1, 'default' => ''),
    'submission_policy' => array('type' => 'text', 'length' => 32, 'notnull' => 1, 'default' => 'single'),
    'updated' => array('type' => 'timestamp')
);
?>
