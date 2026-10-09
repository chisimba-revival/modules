<?php
/** Service-owned storage. @author Derek Keats */
$tablename='tbl_audience_consent';
$options=['type'=>'InnoDB','charset'=>'utf8mb4','collate'=>'utf8mb4_unicode_ci'];
$fields=['id'=>['type'=>'text', 'length'=>32, 'notnull'=>true], 'contact_id'=>['type'=>'text', 'length'=>32, 'notnull'=>true], 'action'=>['type'=>'text', 'length'=>32, 'notnull'=>true], 'source'=>['type'=>'text', 'length'=>191], 'recorded_at'=>['type'=>'text', 'length'=>32], 'wording'=>['type'=>'text', 'length'=>1000]];
$tableIndexes=['primary'=>['fields'=>['id'=>[]], 'primary'=>true]];
