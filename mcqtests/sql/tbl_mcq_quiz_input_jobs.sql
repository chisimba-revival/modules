<?php
/** Durable source snapshots for existing inactive quizzes. @author Derek Keats */
$tablename='tbl_mcq_quiz_input_jobs';
$options=['comment'=>'Queued quiz input generation','collate'=>'utf8mb4_unicode_ci','charset'=>'utf8mb4'];
$fields=[
'id'=>['type'=>'text','length'=>32,'notnull'=>true],
'contextcode'=>['type'=>'text','length'=>255,'notnull'=>true],
'userid'=>['type'=>'text','length'=>32,'notnull'=>true],
'testid'=>['type'=>'text','length'=>32,'notnull'=>true],
'status'=>['type'=>'text','length'=>20,'notnull'=>true],
'input_json'=>['type'=>'clob','notnull'=>true],
'result_json'=>['type'=>'clob'],
'fingerprint'=>['type'=>'text','length'=>64,'notnull'=>true],
'claimtoken'=>['type'=>'text','length'=>32],
'progress_completed'=>['type'=>'integer','default'=>0,'notnull'=>true],
'progress_total'=>['type'=>'integer','notnull'=>true],
'errorcode'=>['type'=>'text','length'=>64],
'date_created'=>['type'=>'timestamp','notnull'=>true],
'date_updated'=>['type'=>'timestamp','notnull'=>true]
];
$tableIndexes=['mcq_input_primary'=>['primary'=>true,'fields'=>['id'=>[]]],'mcq_input_test'=>['unique'=>true,'fields'=>['testid'=>[]]],'mcq_input_queue'=>['fields'=>['status'=>[],'date_created'=>[]]]];
