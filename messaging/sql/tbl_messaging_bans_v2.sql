<?php
/** Context messaging schema. @author Derek Keats */
$tablename='tbl_messaging_bans_v2';
$options=['comment'=>'Context messaging','collate'=>'utf8mb4_unicode_ci','charset'=>'utf8mb4','type'=>'InnoDB'];
$fields=[
 'id'=>['type'=>'text','notnull'=>true,'length'=>32],
 'channel_id'=>['type'=>'text','notnull'=>true,'length'=>32],
 'user_id'=>['type'=>'text','notnull'=>true,'length'=>64],
];
$tableIndexes=[
 'msg_bans_pk'=>['primary'=>true,'fields'=>['id'=>[]]],
 'msg_bans_u0'=>['unique'=>true,'fields'=>['channel_id'=>[],'user_id'=>[]]],
];
