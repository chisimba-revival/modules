<?php
/** Event schema. @author Derek Keats <derek@dkeats.com> */
$tablename='tbl_events_waitlist';
$options=['comment'=>'Events waitlist','type'=>'InnoDB','charset'=>'utf8mb4','collate'=>'utf8mb4_unicode_ci','character_set'=>'utf8mb4'];
$fields=[
    'id'=>['type'=>'text','length'=>32,'notnull'=>true],
    'occurrence_id'=>['type'=>'text','length'=>32,'notnull'=>true],
    'email'=>['type'=>'text','length'=>254,'notnull'=>true],
    'name'=>['type'=>'text','length'=>191,'notnull'=>true],
    'state'=>['type'=>'text','length'=>20,'notnull'=>true],
    'created_at'=>['type'=>'integer','notnull'=>true],
    'expires_at'=>['type'=>'integer','notnull'=>true],
];
$tableIndexes=['events_waitlist_pk'=>['unique'=>true,'fields'=>['id'=>[]]],
    'events_waitlist_occurrence_id'=>['fields'=>['occurrence_id'=>[]]],
];
