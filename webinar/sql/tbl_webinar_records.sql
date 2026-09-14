<?php
/** Webinar-owned archive and speaker records, with immutable source identity. @author Derek Keats */
$tablename='tbl_webinar_records';
$options=['comment'=>'Webinar archive records','collate'=>'utf8_general_ci','character_set'=>'utf8'];
$fields=[
 'id'=>['type'=>'text','length'=>32,'notnull'=>true],
 'kind'=>['type'=>'text','length'=>16,'notnull'=>true],
 'source_key'=>['type'=>'text','length'=>191,'notnull'=>true],
 'source_hash'=>['type'=>'text','length'=>64,'notnull'=>true],
 'title'=>['type'=>'text','length'=>500,'notnull'=>true],
 'status'=>['type'=>'text','length'=>16,'notnull'=>true],
 'presented_at'=>['type'=>'text','length'=>32],
 'payload'=>['type'=>'clob','notnull'=>true]
];
$tableIndexes=['webinar_primary'=>['primary'=>true,'fields'=>['id'=>[]]],'webinar_source'=>['unique'=>true,'fields'=>['source_key'=>[]]]];
