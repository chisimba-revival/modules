<?php
/** Context messaging schema. @author Derek Keats */
$tablename='tbl_messaging_channels_v2';
$options=['comment'=>'Context messaging','collate'=>'utf8mb4_unicode_ci','charset'=>'utf8mb4','type'=>'InnoDB'];
$fields=[
 'id'=>['type'=>'text','notnull'=>true,'length'=>32],
 'context_code'=>['type'=>'text','notnull'=>true,'length'=>32],
 'last_sequence'=>['type'=>'integer','notnull'=>true,'length'=>8],
];
$tableIndexes=[
 'msg_channels_pk'=>['primary'=>true,'fields'=>['id'=>[]]],
 'msg_channels_u0'=>['unique'=>true,'fields'=>['context_code'=>[]]],
];
