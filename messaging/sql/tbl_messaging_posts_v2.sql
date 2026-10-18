<?php
/** Context messaging schema. @author Derek Keats */
$tablename='tbl_messaging_posts_v2';
$options=['comment'=>'Context messaging','collate'=>'utf8mb4_unicode_ci','charset'=>'utf8mb4','type'=>'InnoDB'];
$fields=[
 'id'=>['type'=>'text','notnull'=>true,'length'=>32],
 'channel_id'=>['type'=>'text','notnull'=>true,'length'=>32],
 'sender_id'=>['type'=>'text','notnull'=>true,'length'=>64],
 'submission_id'=>['type'=>'text','notnull'=>true,'length'=>36],
 'body'=>['type'=>'clob','notnull'=>true],
 'sequence_no'=>['type'=>'integer','notnull'=>true,'length'=>8],
 'created_at'=>['type'=>'timestamp','notnull'=>true],
];
$tableIndexes=[
 'msg_posts_pk'=>['primary'=>true,'fields'=>['id'=>[]]],
 'msg_posts_u0'=>['unique'=>true,'fields'=>['channel_id'=>[],'sender_id'=>[],'submission_id'=>[]]],
 'msg_posts_u1'=>['unique'=>true,'fields'=>['channel_id'=>[],'sequence_no'=>[]]],
];
