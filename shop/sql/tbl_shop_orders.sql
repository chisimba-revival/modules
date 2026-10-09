<?php
/** Shop schema. @author Derek Keats <derek@dkeats.com> */
$tablename='tbl_shop_orders';
$options=['comment'=>'Shop orders','type'=>'InnoDB','charset'=>'utf8mb4','collate'=>'utf8mb4_unicode_ci','character_set'=>'utf8mb4'];
$fields=[
    'user_id'=>['type'=>'text','length'=>25,'notnull'=>false],
    'id'=>['type'=>'text','length'=>32,'notnull'=>true],
    'request_hash'=>['type'=>'text','length'=>64,'notnull'=>true],
    'email'=>['type'=>'text','length'=>254,'notnull'=>true],
    'address_json'=>['type'=>'clob','notnull'=>true],
    'snapshot'=>['type'=>'clob','notnull'=>true],
    'payment_state'=>['type'=>'text','length'=>24,'notnull'=>true],
    'fulfilment_state'=>['type'=>'text','length'=>24,'notnull'=>true],
    'stock_applied'=>['type'=>'integer','notnull'=>true],
    'hold_until'=>['type'=>'integer','notnull'=>true],
    'intent_id'=>['type'=>'text','length'=>32,'notnull'=>true],
    'checkout_state'=>['type'=>'text','length'=>24,'notnull'=>true],
    'checkout_url'=>['type'=>'text','length'=>2000,'notnull'=>true],
    'courier'=>['type'=>'text','length'=>120,'notnull'=>true],
    'tracking'=>['type'=>'text','length'=>191,'notnull'=>true],
    'revision'=>['type'=>'integer','notnull'=>true],
    'created_at'=>['type'=>'integer','notnull'=>true],
    'updated_at'=>['type'=>'integer','notnull'=>true],
];
$tableIndexes=['shop_orders_id'=>['unique'=>true,'fields'=>['id'=>[]]],
    'shop_orders_request'=>['unique'=>true,'fields'=>['request_hash'=>[]]],
    'shop_orders_created'=>['fields'=>['created_at'=>[]]],
];
