<?php
/** Shop schema. @author Derek Keats <derek@dkeats.com> */
$tablename='tbl_shop_settings';
$options=['comment'=>'Shop settings','type'=>'InnoDB','charset'=>'utf8mb4','collate'=>'utf8mb4_unicode_ci','character_set'=>'utf8mb4'];
$fields=[
    'id'=>['type'=>'text','length'=>32,'notnull'=>true],
    'settings_json'=>['type'=>'clob','notnull'=>true],
    'revision'=>['type'=>'integer','notnull'=>true],
];
$tableIndexes=['shop_settings_id'=>['unique'=>true,'fields'=>['id'=>[]]],
];
