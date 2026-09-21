<?php
$tablename='tbl_mcqgenerator_exams';
$options=['comment'=>'Private exams assembled from saved chapter questions','charset'=>'utf8mb4','character_set'=>'utf8mb4','collate'=>'utf8mb4_unicode_ci'];
$fields=[
'id'=>['type'=>'text','length'=>32,'notnull'=>true],
'ownerid'=>['type'=>'text','length'=>64,'notnull'=>true],
'title'=>['type'=>'text','length'=>200,'notnull'=>true],
'content_json'=>['type'=>'clob','notnull'=>true],
'version'=>['type'=>'integer','default'=>1,'notnull'=>true],
'datecreated'=>['type'=>'timestamp','notnull'=>true],
'datemodified'=>['type'=>'timestamp','notnull'=>true]
];
$tableIndexes=['mcqexam_owner'=>['fields'=>['ownerid'=>[]]]];
