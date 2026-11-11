<?php
/** Sights & Sounds practice records. @author Derek Keats */
$tablename = 'tbl_ss_runs';
$options = ['comment'=>'Sights & Sounds practice', 'collate'=>'utf8mb4_unicode_ci', 'character_set'=>'utf8mb4'];
$fields = [
    'id' => ['type'=>'text', 'length'=>32],
    'contextcode' => ['type'=>'text', 'length'=>255],
    'activityid' => ['type'=>'text', 'length'=>32],
    'userid' => ['type'=>'text', 'length'=>32],
    'state' => ['type'=>'clob'],
    'revision' => ['type'=>'integer', 'length'=>8],
    'createdat' => ['type'=>'text', 'length'=>32],
];
$tableIndexes = ['scope'=>['fields'=>['contextcode'=>[]]]];
