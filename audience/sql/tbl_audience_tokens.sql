<?php
/** Service-owned storage. @author Derek Keats */
$tablename='tbl_audience_tokens';
$options=['type'=>'InnoDB','charset'=>'utf8mb4','collate'=>'utf8mb4_unicode_ci'];
$fields=['id'=>['type'=>'text', 'length'=>32, 'notnull'=>true], 'contact_id'=>['type'=>'text', 'length'=>32, 'notnull'=>true], 'token_hash'=>['type'=>'text', 'length'=>64, 'notnull'=>true], 'created_at'=>['type'=>'text', 'length'=>32]];
$tableIndexes=['token'=>['fields'=>['token_hash'=>[]], 'unique'=>true]];
