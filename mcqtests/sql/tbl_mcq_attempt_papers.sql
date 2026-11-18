<?php
/** Frozen learner selections and answer keys. Never sent as JSON to learners. @author Derek Keats */
$tablename='tbl_mcq_attempt_papers';
$options=['comment'=>'Immutable quiz attempt papers','collate'=>'utf8mb4_unicode_ci','charset'=>'utf8mb4'];
$fields=['id'=>['type'=>'text','length'=>32,'notnull'=>true],'testid'=>['type'=>'text','length'=>32,'notnull'=>true],'userid'=>['type'=>'text','length'=>32,'notnull'=>true],'paper_json'=>['type'=>'clob','notnull'=>true],'totalmark'=>['type'=>'integer','notnull'=>true]];
$tableIndexes=['mcq_paper_primary'=>['primary'=>true,'fields'=>['id'=>[]]],'mcq_paper_test'=>['fields'=>['testid'=>[],'userid'=>[]]]];
