<?php
/** Spoken assessment storage. @author Derek Keats */
$tablename = 'tbl_spoken_activities';
$options = ['comment'=>'Private formative spoken assessment', 'collate'=>'utf8mb4_unicode_ci', 'charset'=>'utf8mb4'];
$fields = [
    'id' => ['type'=>'text','length'=>32],
    'contextcode' => ['type'=>'text','length'=>255],
    'userid' => ['type'=>'text','length'=>64],
    'title' => ['type'=>'text','length'=>200],
    'prompt' => ['type'=>'clob'],
    'outcomes' => ['type'=>'clob'],
    'rubric_id' => ['type'=>'text','length'=>32],
    'published' => ['type'=>'integer'],
    'version' => ['type'=>'integer'],
    'date_created' => ['type'=>'timestamp'],
    'date_updated' => ['type'=>'timestamp'],
];
$tableIndexes = [
    'tbl_spoken_activities_pk'=>['unique'=>true,'fields'=>['id'=>[]]],
    'spoken_activity_context'=>['fields'=>['contextcode'=>[]]],
];
