<?php
/** @author Derek Keats */
$tablename = 'tbl_discussion_assessment_marks';
$options = array('collate'=>'utf8_general_ci', 'character_set'=>'utf8');
$fields = array(
    'id'=>array('type'=>'text','length'=>32,'notnull'=>1),
    'discussion_id'=>array('type'=>'text','length'=>32,'notnull'=>1),
    'user_id'=>array('type'=>'text','length'=>25,'notnull'=>1),
    'mark'=>array('type'=>'float','notnull'=>1),
    'feedback'=>array('type'=>'text','length'=>2000),
    'rubric_json'=>array('type'=>'clob'),
    'ai_job_id'=>array('type'=>'text','length'=>32),
    'marker_id'=>array('type'=>'text','length'=>25,'notnull'=>1),
    'date_created'=>array('type'=>'timestamp'),
    'date_updated'=>array('type'=>'timestamp')
);
$name = 'tbl_discussion_assessment_marks_idx';
$indexes = array('fields'=>array('discussion_id'=>array(),'user_id'=>array()));
