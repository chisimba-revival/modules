<?php
/** Event schema. @author Derek Keats <derek@dkeats.com> */
$tablename='tbl_events_bookings';
$options=['comment'=>'Events bookings','type'=>'InnoDB','charset'=>'utf8mb4','collate'=>'utf8mb4_unicode_ci','character_set'=>'utf8mb4'];
$fields=[
    'id'=>['type'=>'text','length'=>32,'notnull'=>true],
    'occurrence_id'=>['type'=>'text','length'=>32,'notnull'=>true],
    'request_hash'=>['type'=>'text','length'=>64,'notnull'=>true],
    'name'=>['type'=>'text','length'=>191,'notnull'=>true],
    'email'=>['type'=>'text','length'=>254,'notnull'=>true],
    'quantity'=>['type'=>'integer','notnull'=>true],
    'state'=>['type'=>'text','length'=>32,'notnull'=>true],
    'expires_at'=>['type'=>'integer','notnull'=>true],
    'created_at'=>['type'=>'integer','notnull'=>true],
    'product_code'=>['type'=>'text','length'=>96,'notnull'=>true],
    'price_version'=>['type'=>'text','length'=>64,'notnull'=>true],
    'amount_minor'=>['type'=>'integer','notnull'=>true],
    'vat_minor'=>['type'=>'integer','notnull'=>true],
    'currency'=>['type'=>'text','length'=>3,'notnull'=>true],
    'provider'=>['type'=>'text','length'=>16,'notnull'=>true],
    'details'=>['type'=>'clob','notnull'=>true],
];
$tableIndexes=['events_bookings_pk'=>['unique'=>true,'fields'=>['id'=>[]]],
    'events_bookings_request_hash'=>['unique'=>true,'fields'=>['request_hash'=>[]]],
    'events_bookings_occurrence_id'=>['fields'=>['occurrence_id'=>[]]],
];
