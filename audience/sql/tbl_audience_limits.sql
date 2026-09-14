<?php
/** Service-owned storage. @author Derek Keats */
$tablename='tbl_audience_limits';
$options=['type'=>'InnoDB','charset'=>'utf8mb4','collate'=>'utf8mb4_unicode_ci'];
$fields=['id'=>['type'=>'text', 'length'=>32, 'notnull'=>true], 'attempts'=>['type'=>'integer', 'notnull'=>true], 'expires_at'=>['type'=>'integer', 'notnull'=>true]];
$tableIndexes=['primary'=>['fields'=>['id'=>[]], 'primary'=>true]];
