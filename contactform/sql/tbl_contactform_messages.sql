<?php
$tablename='tbl_contactform_messages';
$options=['type'=>'InnoDB','comment'=>'Private contact submissions and delivery state','collate'=>'utf8_general_ci','character_set'=>'utf8'];
$fields=['id'=>['type'=>'text','length'=>32,'notnull'=>true],'name'=>['type'=>'text','length'=>150,'notnull'=>true],'email'=>['type'=>'text','length'=>254,'notnull'=>true],'subject'=>['type'=>'text','length'=>200,'notnull'=>true],'message'=>['type'=>'clob','notnull'=>true],'fingerprint'=>['type'=>'text','length'=>64,'notnull'=>true],'status'=>['type'=>'text','length'=>20,'notnull'=>true],'datecreated'=>['type'=>'timestamp'],'datemodified'=>['type'=>'timestamp']];
$tableIndexes=['contactform_primary'=>['primary'=>true,'fields'=>['id'=>[]]],'contactform_status'=>['fields'=>['status'=>[],'datecreated'=>[]]]];
