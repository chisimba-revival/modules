<?php
/** Service-owned storage. @author Derek Keats */
$tablename='tbl_audience_contacts';
$options=['type'=>'InnoDB','charset'=>'utf8mb4','collate'=>'utf8mb4_unicode_ci'];
$fields=['id'=>['type'=>'text', 'length'=>32, 'notnull'=>true], 'email'=>['type'=>'text', 'length'=>254, 'notnull'=>true], 'name'=>['type'=>'text', 'length'=>200, 'notnull'=>true], 'userid'=>['type'=>'text', 'length'=>25], 'state'=>['type'=>'text', 'length'=>16, 'notnull'=>true], 'revision'=>['type'=>'integer', 'notnull'=>true, 'default'=>0], 'created_at'=>['type'=>'text', 'length'=>32], 'verified_at'=>['type'=>'text', 'length'=>32]];
$tableIndexes=['email'=>['fields'=>['email'=>[]], 'unique'=>true]];
