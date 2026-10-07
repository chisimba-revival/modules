<?php
/** Shop schema. @author Derek Keats <derek@dkeats.com> */
$tablename='tbl_shop_books';
$options=['comment'=>'Shop books','type'=>'InnoDB','charset'=>'utf8mb4','collate'=>'utf8mb4_unicode_ci','character_set'=>'utf8mb4'];
$fields=[
    'id'=>['type'=>'text','length'=>32,'notnull'=>true],
    'title'=>['type'=>'text','length'=>191,'notnull'=>true],
    'isbn'=>['type'=>'text','length'=>32,'notnull'=>true],
    'description'=>['type'=>'clob','notnull'=>true],
    'image_url'=>['type'=>'text','length'=>1500,'notnull'=>true],
    'price_minor'=>['type'=>'integer','notnull'=>true],
    'stock'=>['type'=>'integer','notnull'=>true],
    'status'=>['type'=>'text','length'=>20,'notnull'=>true],
    'revision'=>['type'=>'integer','notnull'=>true],
];
$tableIndexes=['shop_books_id'=>['unique'=>true,'fields'=>['id'=>[]]],
];
