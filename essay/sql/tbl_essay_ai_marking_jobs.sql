<?php
/** Durable background jobs for AI-assisted Essay marking. */
$tablename='tbl_essay_ai_marking_jobs';
$options=array('comment'=>'Durable AI Essay marking suggestion jobs','collate'=>'utf8_general_ci','character_set'=>'utf8');
$fields=array(
 'id'=>array('type'=>'text','length'=>32,'notnull'=>TRUE), 'contextcode'=>array('type'=>'text','length'=>255,'notnull'=>TRUE),
 'userid'=>array('type'=>'text','length'=>32,'notnull'=>TRUE), 'booking_id'=>array('type'=>'text','length'=>32,'notnull'=>TRUE),
 'status'=>array('type'=>'text','length'=>20,'notnull'=>TRUE), 'result_json'=>array('type'=>'clob'), 'error_code'=>array('type'=>'text','length'=>80),
 'date_created'=>array('type'=>'timestamp','notnull'=>TRUE), 'date_updated'=>array('type'=>'timestamp','notnull'=>TRUE), 'date_completed'=>array('type'=>'timestamp'),
);
$tableIndexes=array(
 'essay_ai_marking_jobs_primary'=>array('primary'=>TRUE,'fields'=>array('id'=>array())),
 'essay_ai_marking_jobs_owner'=>array('fields'=>array('userid'=>array(),'contextcode'=>array())),
 'essay_ai_marking_jobs_status'=>array('fields'=>array('status'=>array(),'date_updated'=>array())),
);
?>
