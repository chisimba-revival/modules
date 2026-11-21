<?php
$tablename = 'tbl_grassguide_audit';
$options = ['type'=>'InnoDB','charset'=>'utf8mb4','collate'=>'utf8mb4_unicode_ci'];
$fields = [
    'id'=>['type'=>'text','length'=>32,'notnull'=>true],
    'species_id'=>['type'=>'text','length'=>32,'notnull'=>true],
    'actor'=>['type'=>'text','length'=>25,'notnull'=>true],
    'action'=>['type'=>'text','length'=>40,'notnull'=>true],
    'snapshot'=>['type'=>'clob','notnull'=>true],
    'created_at'=>['type'=>'timestamp','notnull'=>true],
];
$tableIndexes = [
    'grassguide_audit_id'=>['fields'=>['id'=>[]],'unique'=>true],
    'grassguide_audit_species'=>['fields'=>['species_id'=>[]]],
];
