<?php
/** Sights & Sounds practice records. @author Derek Keats */
$tablename = 'tbl_ss_activities';
$options = ['comment'=>'Sights & Sounds practice', 'collate'=>'utf8_general_ci', 'character_set'=>'utf8'];
$fields = [
    'id' => ['type'=>'text', 'length'=>32],
    'contextcode' => ['type'=>'text', 'length'=>255],
    'title' => ['type'=>'text', 'length'=>200],
    'instructions' => ['type'=>'clob'],
    'questioncount' => ['type'=>'integer', 'length'=>4],
    'itemids' => ['type'=>'clob'],
    'createdby' => ['type'=>'text', 'length'=>32],
    'createdat' => ['type'=>'text', 'length'=>32],
];
$tableIndexes = ['scope'=>['fields'=>['contextcode'=>[]]]];
