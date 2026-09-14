<?php
/** Service-owned storage. @author Derek Keats */
$tablename='tbl_webinar_registrations';
$options=['type'=>'InnoDB','charset'=>'utf8mb4','collate'=>'utf8mb4_unicode_ci'];
$fields=['id'=>['type'=>'text', 'length'=>32, 'notnull'=>true], 'webinar_id'=>['type'=>'text', 'length'=>32, 'notnull'=>true], 'contact_id'=>['type'=>'text', 'length'=>32, 'notnull'=>true], 'state'=>['type'=>'text', 'length'=>16, 'notnull'=>true], 'confirm_hash'=>['type'=>'text', 'length'=>64, 'notnull'=>true], 'expires_at'=>['type'=>'integer', 'notnull'=>true], 'contact_revision'=>['type'=>'integer', 'notnull'=>true], 'created_at'=>['type'=>'text', 'length'=>32], 'confirmed_at'=>['type'=>'text', 'length'=>32]];
$tableIndexes=['registration'=>['fields'=>['webinar_id'=>[], 'contact_id'=>[]], 'unique'=>true], 'confirm'=>['fields'=>['confirm_hash'=>[]], 'unique'=>true]];
