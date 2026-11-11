<?php
/** Sights & Sounds practice records. @author Derek Keats */
$tablename = 'tbl_ss_items';
$options = ['comment'=>'Sights & Sounds practice', 'collate'=>'utf8_general_ci', 'character_set'=>'utf8'];
$fields = [
    'id' => ['type'=>'text', 'length'=>32],
    'contextcode' => ['type'=>'text', 'length'=>255],
    'fileid' => ['type'=>'text', 'length'=>32],
    'kind' => ['type'=>'text', 'length'=>10],
    'answer' => ['type'=>'text', 'length'=>160],
    'aliases' => ['type'=>'clob'],
    'feedback' => ['type'=>'clob'],
    'credit' => ['type'=>'text', 'length'=>500],
    'createdby' => ['type'=>'text', 'length'=>32],
    'createdat' => ['type'=>'text', 'length'=>32],
];
$tableIndexes = ['scope'=>['fields'=>['contextcode'=>[]]]];
