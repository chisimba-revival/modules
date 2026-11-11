<?php
/** Assignment rules and audited permissions. @author Derek Keats */
$tablename='tbl_ss_attempt_grants';
$options=['collate'=>'utf8mb4_unicode_ci','character_set'=>'utf8mb4'];
$fields=['id'=>['type'=>'text','length'=>32],'activityid'=>['type'=>'text','length'=>32], 'contextcode'=>['type'=>'text','length'=>255], 'userid'=>['type'=>'text','length'=>32], 'actor'=>['type'=>'text','length'=>32], 'reason'=>['type'=>'text','length'=>1000], 'createdat'=>['type'=>'text','length'=>32], 'requestid'=>['type'=>'text','length'=>32]];
$tableIndexes=['identity'=>['fields'=>['requestid'=>[]],'unique'=>true], 'scope'=>['fields'=>['contextcode'=>[]]]];
