<?php
$tablename = 'tbl_grassguide_media';
$options = ['type'=>'InnoDB','charset'=>'utf8mb4','collate'=>'utf8mb4_unicode_ci'];
$fields = [
    'id'=>['type'=>'text','length'=>32,'notnull'=>true],
    'species_id'=>['type'=>'text','length'=>32,'notnull'=>true],
    'payload'=>['type'=>'clob','notnull'=>true],
    'revision'=>['type'=>'integer','notnull'=>true,'default'=>1],
    'created_at'=>['type'=>'timestamp','notnull'=>true],
    'updated_at'=>['type'=>'timestamp','notnull'=>true],
];
$tableIndexes = [
    'grassguide_media_id'=>['fields'=>['id'=>[]],'unique'=>true],
    'grassguide_media_species'=>['fields'=>['species_id'=>[]]],
];
