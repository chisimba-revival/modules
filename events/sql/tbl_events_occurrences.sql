<?php
/** Event schema. @author Derek Keats <derek@dkeats.com> */
$tablename='tbl_events_occurrences';
$options=['comment'=>'Events occurrences','type'=>'InnoDB','charset'=>'utf8mb4','collate'=>'utf8mb4_unicode_ci','character_set'=>'utf8mb4'];
$fields=[
    'id'=>['type'=>'text','length'=>32,'notnull'=>true],
    'event_id'=>['type'=>'text','length'=>32,'notnull'=>true],
    'starts_at'=>['type'=>'integer','notnull'=>true],
    'ends_at'=>['type'=>'integer','notnull'=>true],
    'closes_at'=>['type'=>'integer','notnull'=>true],
    'timezone'=>['type'=>'text','length'=>64,'notnull'=>true],
    'capacity'=>['type'=>'integer','notnull'=>true],
    'status'=>['type'=>'text','length'=>20,'notnull'=>true],
    'revision'=>['type'=>'integer','notnull'=>true],
    'product_code'=>['type'=>'text','length'=>96,'notnull'=>true],
    'price_version'=>['type'=>'text','length'=>64,'notnull'=>true],
    'private_details'=>['type'=>'clob','notnull'=>true],
    'details'=>['type'=>'clob','notnull'=>true],
];
$tableIndexes=['events_occurrences_pk'=>['unique'=>true,'fields'=>['id'=>[]]],
    'events_occurrences_event_id'=>['fields'=>['event_id'=>[]]],
];
