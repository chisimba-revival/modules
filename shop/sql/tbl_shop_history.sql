<?php
/** Shop schema. @author Derek Keats <derek@dkeats.com> */
$tablename='tbl_shop_history';
$options=['comment'=>'Shop history','type'=>'InnoDB','charset'=>'utf8mb4','collate'=>'utf8mb4_unicode_ci','character_set'=>'utf8mb4'];
$fields=[
    'id'=>['type'=>'text','length'=>32,'notnull'=>true],
    'order_id'=>['type'=>'text','length'=>32,'notnull'=>true],
    'event'=>['type'=>'text','length'=>32,'notnull'=>true],
    'actor_id'=>['type'=>'text','length'=>25,'notnull'=>true],
    'created_at'=>['type'=>'integer','notnull'=>true],
];
$tableIndexes=['shop_history_id'=>['unique'=>true,'fields'=>['id'=>[]]],
    'shop_history_order'=>['fields'=>['order_id'=>[]]],
];
