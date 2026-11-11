<?php
/** Immutable practice receipts shared by web and mobile. @author Derek Keats */
$tablename='tbl_ss_completions';
$options=['comment'=>'Sights and Sounds completion receipts','collate'=>'utf8mb4_unicode_ci','character_set'=>'utf8mb4'];
$fields=[
'id'=>['type'=>'text','length'=>32],
'runid'=>['type'=>'text','length'=>32],
'contextcode'=>['type'=>'text','length'=>255],
'activityid'=>['type'=>'text','length'=>32],
'userid'=>['type'=>'text','length'=>32],
'digest'=>['type'=>'text','length'=>64],
'correct'=>['type'=>'integer','length'=>4],
'total'=>['type'=>'integer','length'=>4],
'createdat'=>['type'=>'text','length'=>32],
];
$tableIndexes=['run_unique'=>['fields'=>['runid'=>[]],'unique'=>true],'scope'=>['fields'=>['contextcode'=>[]]]];
