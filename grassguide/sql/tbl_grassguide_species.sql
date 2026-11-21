<?php
$tablename = 'tbl_grassguide_species';
$options = ['type'=>'InnoDB','charset'=>'utf8mb4','collate'=>'utf8mb4_unicode_ci'];
$fields = [
    'id'=>['type'=>'text','length'=>32,'notnull'=>true],
    'scientific_name'=>['type'=>'text','length'=>201,'notnull'=>true],
    'source_key'=>['type'=>'text','length'=>230],
    'payload'=>['type'=>'clob','notnull'=>true],
    'revision'=>['type'=>'integer','notnull'=>true,'default'=>1],
    'updated_at'=>['type'=>'timestamp','notnull'=>true],
    'updated_by'=>['type'=>'text','length'=>25,'notnull'=>true],
];
$tableIndexes = [
    'grassguide_species_id'=>['fields'=>['id'=>[]],'unique'=>true],
    'grassguide_species_name'=>['fields'=>['scientific_name'=>[]],'unique'=>true],
    'grassguide_species_source'=>['fields'=>['source_key'=>[]],'unique'=>true],
];
