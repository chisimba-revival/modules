<?php
$tablename = 'tbl_offlineassessment_marks';
$options = array('comment'=>'Current authoritative offline assessment marks','collate'=>'utf8_general_ci','character_set'=>'utf8');
$fields = array(
 'id'=>array('type'=>'text','length'=>32,'notnull'=>TRUE),
 'assessment_id'=>array('type'=>'text','length'=>32,'notnull'=>TRUE),
 'student_id'=>array('type'=>'text','length'=>255,'notnull'=>TRUE),
 'mark'=>array('type'=>'decimal','length'=>12,'scale'=>3,'notnull'=>TRUE),
 'entered_by'=>array('type'=>'text','length'=>255,'notnull'=>TRUE),
 'date_created'=>array('type'=>'timestamp','notnull'=>TRUE),
 'date_updated'=>array('type'=>'timestamp','notnull'=>TRUE)
);
$tableIndexes = array(
 'offlineassessment_marks_primary'=>array('primary'=>TRUE,'fields'=>array('id'=>array())),
 'offlineassessment_marks_student'=>array('unique'=>TRUE,'fields'=>array('assessment_id'=>array(),'student_id'=>array()))
);
?>