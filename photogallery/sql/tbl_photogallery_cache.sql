<?php
$tablename='tbl_photogallery_cache';$options=['comment'=>'Public provider photo snapshots','collate'=>'utf8_general_ci','character_set'=>'utf8'];
$fields=['id'=>['type'=>'text','length'=>32,'notnull'=>true],'title'=>['type'=>'text','length'=>255,'notnull'=>true],'provider'=>['type'=>'text','length'=>20,'notnull'=>true],'source_url'=>['type'=>'text','length'=>1000,'notnull'=>true],'settings'=>['type'=>'clob','notnull'=>true],'items'=>['type'=>'clob','notnull'=>true],'fetched'=>['type'=>'timestamp','notnull'=>true]];
$tableIndexes=['photogallery_primary'=>['primary'=>true,'fields'=>['id'=>[]]]];
