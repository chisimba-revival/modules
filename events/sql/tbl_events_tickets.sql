<?php
/** Event schema. @author Derek Keats <derek@dkeats.com> */
$tablename='tbl_events_tickets';
$options=['comment'=>'Events tickets','type'=>'InnoDB','charset'=>'utf8mb4','collate'=>'utf8mb4_unicode_ci','character_set'=>'utf8mb4'];
$fields=[
    'id'=>['type'=>'text','length'=>32,'notnull'=>true],
    'booking_id'=>['type'=>'text','length'=>32,'notnull'=>true],
    'occurrence_id'=>['type'=>'text','length'=>32,'notnull'=>true],
    'attendee'=>['type'=>'text','length'=>191,'notnull'=>true],
    'state'=>['type'=>'text','length'=>20,'notnull'=>true],
    'revision'=>['type'=>'integer','notnull'=>true],
    'code_hash'=>['type'=>'text','length'=>64,'notnull'=>true],
    'checked_at'=>['type'=>'integer','notnull'=>true],
    'checked_by'=>['type'=>'text','length'=>25,'notnull'=>true],
];
$tableIndexes=['events_tickets_pk'=>['unique'=>true,'fields'=>['id'=>[]]],
    'events_tickets_code_hash'=>['unique'=>true,'fields'=>['code_hash'=>[]]],
    'events_tickets_booking_id'=>['fields'=>['booking_id'=>[]]],
    'events_tickets_occurrence_id'=>['fields'=>['occurrence_id'=>[]]],
];
