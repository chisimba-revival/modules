<?php
$tablename = 'tbl_offlineassessment_mark_history';
$options = array('comment'=>'Append-only offline assessment mark audit history','collate'=>'utf8_general_ci','character_set'=>'utf8');
$fields = array(
 'id'=>array('type'=>'text','length'=>32,'notnull'=>TRUE),
 'assessment_id'=>array('type'=>'text','length'=>32,'notnull'=>TRUE),
 'student_id'=>array('type'=>'text','length'=>255,'notnull'=>TRUE),
 'old_mark'=>array('type'=>'decimal','length'=>12,'scale'=>3),
 'new_mark'=>array('type'=>'decimal','length'=>12,'scale'=>3,'notnull'=>TRUE),
 'event_type'=>array('type'=>'text','length'=>24,'notnull'=>TRUE),
 'reason'=>array('type'=>'clob'),
 'changed_by'=>array('type'=>'text','length'=>255,'notnull'=>TRUE),
 'date_changed'=>array('type'=>'timestamp','notnull'=>TRUE)
);
$tableIndexes = array(
 'offlineassessment_history_primary'=>array('primary'=>TRUE,'fields'=>array('id'=>array())),
 'offlineassessment_history_assessment'=>array('fields'=>array('assessment_id'=>array(),'student_id'=>array(),'date_changed'=>array()))
);
?>