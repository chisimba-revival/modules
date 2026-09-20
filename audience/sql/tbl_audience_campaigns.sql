<?php
$tablename='tbl_audience_campaigns';
$options=['type'=>'InnoDB','charset'=>'utf8mb4','collate'=>'utf8mb4_unicode_ci'];
$fields=['id'=>['type'=>'text','length'=>32,'notnull'=>true], 'version'=>['type'=>'integer','default'=>1,'notnull'=>true], 'state'=>['type'=>'text','length'=>20,'notnull'=>true], 'subject'=>['type'=>'text','length'=>240,'notnull'=>true], 'payload'=>['type'=>'clob','notnull'=>true], 'created_at'=>['type'=>'text','length'=>32,'notnull'=>true], 'updated_at'=>['type'=>'text','length'=>32,'notnull'=>true]];
$tableIndexes=['id'=>['fields'=>['id'=>[]],'unique'=>true]];
