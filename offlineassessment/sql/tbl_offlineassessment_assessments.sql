<?php
$tablename = 'tbl_offlineassessment_assessments';
$options = array('comment'=>'Offline assessment activities','collate'=>'utf8_general_ci','character_set'=>'utf8');
$fields = array(
 'id'=>array('type'=>'text','length'=>32,'notnull'=>TRUE),
 'context_code'=>array('type'=>'text','length'=>255,'notnull'=>TRUE),
 'type_id'=>array('type'=>'text','length'=>32,'notnull'=>TRUE),
 'name'=>array('type'=>'text','length'=>255,'notnull'=>TRUE),
 'classification'=>array('type'=>'text','length'=>16,'notnull'=>TRUE,'default'=>'summative'),
 'maximum_mark'=>array('type'=>'decimal','length'=>12,'scale'=>3,'notnull'=>TRUE,'default'=>100),
 'assessment_date'=>array('type'=>'timestamp'),
 'description'=>array('type'=>'clob'),
 'status'=>array('type'=>'text','length'=>16,'notnull'=>TRUE,'default'=>'active'),
 'created_by'=>array('type'=>'text','length'=>255,'notnull'=>TRUE),
 'date_created'=>array('type'=>'timestamp','notnull'=>TRUE),
 'date_updated'=>array('type'=>'timestamp','notnull'=>TRUE)
);
$tableIndexes = array(
 'offlineassessment_assessments_primary'=>array('primary'=>TRUE,'fields'=>array('id'=>array())),
 'offlineassessment_assessments_context'=>array('fields'=>array('context_code'=>array(),'status'=>array()))
);
?>