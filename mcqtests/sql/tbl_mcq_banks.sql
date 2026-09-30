<?php
$tablename = 'tbl_mcq_banks';
$options = ['comment'=>'Reusable MCQ banks with explicit course sharing','charset'=>'utf8mb4','character_set'=>'utf8mb4','collate'=>'utf8mb4_unicode_ci'];
$fields = [
'id'=>['type'=>'text','length'=>32,'notnull'=>true],
'managers_json'=>['type'=>'clob','notnull'=>true],
'name'=>['type'=>'text','length'=>200,'notnull'=>true],
'shares_json'=>['type'=>'clob','notnull'=>true],
'version'=>['type'=>'integer','notnull'=>true,'default'=>1],
'createdby'=>['type'=>'text','length'=>64,'notnull'=>true]
];
