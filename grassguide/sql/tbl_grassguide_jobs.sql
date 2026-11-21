<?php
$tablename = 'tbl_grassguide_jobs';
$options = ['type'=>'InnoDB','charset'=>'utf8mb4','collate'=>'utf8mb4_unicode_ci'];
$fields = [
    'id'=>['type'=>'text','length'=>32,'notnull'=>true],
    'species_id'=>['type'=>'text','length'=>32,'notnull'=>true],
    'provider'=>['type'=>'text','length'=>20,'notnull'=>true],
    'status'=>['type'=>'text','length'=>20,'notnull'=>true],
    'due_at'=>['type'=>'timestamp','notnull'=>true],
    'updated_at'=>['type'=>'timestamp','notnull'=>true],
    'attempts'=>['type'=>'integer','notnull'=>true,'default'=>0],
    'result_json'=>['type'=>'clob','notnull'=>true],
    'last_error'=>['type'=>'text','length'=>250,'notnull'=>true],
];
$tableIndexes = [
    'grassguide_jobs_id'=>['fields'=>['id'=>[]],'unique'=>true],
    'grassguide_jobs_due'=>['fields'=>['provider'=>[], 'status'=>[], 'due_at'=>[]]],
];
