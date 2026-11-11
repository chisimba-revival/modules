<?php
/** Assignment rules and audited permissions. @author Derek Keats */
$tablename='tbl_ss_assignment_rules';
$options=['collate'=>'utf8mb4_unicode_ci','character_set'=>'utf8mb4'];
$fields=['id'=>['type'=>'text','length'=>32],'activityid'=>['type'=>'text','length'=>32], 'contextcode'=>['type'=>'text','length'=>255], 'rules'=>['type'=>'clob']];
$tableIndexes=['identity'=>['fields'=>['activityid'=>[]],'unique'=>true], 'scope'=>['fields'=>['contextcode'=>[]]]];
