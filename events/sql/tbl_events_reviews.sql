<?php
/** Event schema. @author Derek Keats <derek@dkeats.com> */
$tablename='tbl_events_reviews';
$options=['comment'=>'Events reviews','type'=>'InnoDB','charset'=>'utf8mb4','collate'=>'utf8mb4_unicode_ci','character_set'=>'utf8mb4'];
$fields=[
    'id'=>['type'=>'text','length'=>32,'notnull'=>true],
    'ticket_id'=>['type'=>'text','length'=>32,'notnull'=>true],
    'event_id'=>['type'=>'text','length'=>32,'notnull'=>true],
    'rating'=>['type'=>'integer','notnull'=>true],
    'comment'=>['type'=>'clob','notnull'=>true],
    'display_name'=>['type'=>'text','length'=>191,'notnull'=>true],
    'publish_consent'=>['type'=>'integer','notnull'=>true],
    'status'=>['type'=>'text','length'=>20,'notnull'=>true],
    'reply'=>['type'=>'clob','notnull'=>true],
    'moderation_reason'=>['type'=>'text','length'=>500,'notnull'=>true],
    'created_at'=>['type'=>'integer','notnull'=>true],
];
$tableIndexes=['events_reviews_pk'=>['unique'=>true,'fields'=>['id'=>[]]],
    'events_reviews_ticket_id'=>['unique'=>true,'fields'=>['ticket_id'=>[]]],
    'events_reviews_event_id'=>['fields'=>['event_id'=>[]]],
];
