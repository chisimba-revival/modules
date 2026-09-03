<?php
/** Durable, auditable AI marking suggestions for Discussion. @author Derek Keats */
$tablename='tbl_discussion_ai_marking_jobs';
$options=array('comment'=>'Human-reviewed Discussion AI marking suggestions','collate'=>'utf8_general_ci','character_set'=>'utf8');
$fields=array(
 'id'=>array('type'=>'text','length'=>32,'notnull'=>TRUE),'contextcode'=>array('type'=>'text','length'=>32,'notnull'=>TRUE),
 'discussion_id'=>array('type'=>'text','length'=>32,'notnull'=>TRUE),'student_id'=>array('type'=>'text','length'=>25,'notnull'=>TRUE),
 'requester_id'=>array('type'=>'text','length'=>25,'notnull'=>TRUE),'status'=>array('type'=>'text','length'=>20,'notnull'=>TRUE),
 'rubric_version'=>array('type'=>'text','length'=>80,'notnull'=>TRUE),'evidence_json'=>array('type'=>'clob'),
 'result_json'=>array('type'=>'clob'),'error_code'=>array('type'=>'text','length'=>80),
 'date_created'=>array('type'=>'timestamp','notnull'=>TRUE),'date_updated'=>array('type'=>'timestamp','notnull'=>TRUE),'date_completed'=>array('type'=>'timestamp')
);
$tableIndexes=array(
 'discussion_ai_jobs_primary'=>array('primary'=>TRUE,'fields'=>array('id'=>array())),
 'discussion_ai_jobs_resource'=>array('fields'=>array('discussion_id'=>array(),'student_id'=>array())),
 'discussion_ai_jobs_status'=>array('fields'=>array('status'=>array(),'date_updated'=>array()))
);
