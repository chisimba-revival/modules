<?php
$tablename = 'tbl_mcq_bank_items';
$options = ['comment'=>'Independent bank question snapshots and metadata','charset'=>'utf8mb4','character_set'=>'utf8mb4','collate'=>'utf8mb4_unicode_ci'];
$fields = [
'id'=>['type'=>'text','length'=>32,'notnull'=>true],
'bankid'=>['type'=>'text','length'=>32,'notnull'=>true],
'fingerprint'=>['type'=>'text','length'=>64,'notnull'=>true],
'stemkey'=>['type'=>'text','length'=>64,'notnull'=>true],
'content_json'=>['type'=>'clob','notnull'=>true]
];
$tableIndexes = ['mcq_bank_identity'=>['fields'=>['bankid'=>[],'fingerprint'=>[]],'unique'=>true]];
