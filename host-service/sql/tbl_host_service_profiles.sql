<?php
/** Shared host profiles. @author Derek Keats <derek@dkeats.com> */
$tablename='tbl_host_service_profiles';
$options=['comment'=>'Public host profiles','type'=>'InnoDB','charset'=>'utf8mb4','collate'=>'utf8mb4_unicode_ci','character_set'=>'utf8mb4'];
$fields=['id'=>['type'=>'text','length'=>32,'notnull'=>true],'owner_id'=>['type'=>'text','length'=>25,'notnull'=>true],'name'=>['type'=>'text','length'=>191,'notnull'=>true],'biography'=>['type'=>'clob','notnull'=>true],'image_url'=>['type'=>'text','length'=>1000,'notnull'=>true]];
$tableIndexes=['host_service_id'=>['unique'=>true,'fields'=>['id'=>[]]]];
