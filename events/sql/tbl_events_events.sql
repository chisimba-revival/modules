<?php
/** Event schema. @author Derek Keats <derek@dkeats.com> */
$tablename='tbl_events_events';
$options=['comment'=>'Events events','type'=>'InnoDB','charset'=>'utf8mb4','collate'=>'utf8mb4_unicode_ci','character_set'=>'utf8mb4'];
$fields=[
    'id'=>['type'=>'text','length'=>32,'notnull'=>true],
    'owner_id'=>['type'=>'text','length'=>25,'notnull'=>true],
    'title'=>['type'=>'text','length'=>191,'notnull'=>true],
    'summary'=>['type'=>'text','length'=>500,'notnull'=>true],
    'description'=>['type'=>'clob','notnull'=>true],
    'status'=>['type'=>'text','length'=>20,'notnull'=>true],
    'revision'=>['type'=>'integer','notnull'=>true],
    'details'=>['type'=>'clob','notnull'=>true],
];
$tableIndexes=['events_events_pk'=>['unique'=>true,'fields'=>['id'=>[]]],
    'events_events_owner_id'=>['fields'=>['owner_id'=>[]]],
];
