<?php
$tablename='tbl_curatedfaq_items';
$options=['comment'=>'Public FAQ questions and protected editorial drafts','collate'=>'utf8_general_ci','character_set'=>'utf8'];
$fields=[
'id'=>['type'=>'text','length'=>32,'notnull'=>true],
'question'=>['type'=>'text','length'=>250,'notnull'=>true],
'answer'=>['type'=>'clob','notnull'=>true],
'status'=>['type'=>'text','length'=>20,'notnull'=>true,'default'=>'draft'],
'display_order'=>['type'=>'integer','notnull'=>true,'default'=>0],
'revision'=>['type'=>'integer','notnull'=>true,'default'=>1],
'creatorid'=>['type'=>'text','length'=>32],
'modifierid'=>['type'=>'text','length'=>32],
'datecreated'=>['type'=>'timestamp'],
'datemodified'=>['type'=>'timestamp'],
'source_key'=>['type'=>'text','length'=>190],
'source_hash'=>['type'=>'text','length'=>64],
];
$tableIndexes=['curatedfaq_primary'=>['primary'=>true,'fields'=>['id'=>[]]],'curatedfaq_source'=>['unique'=>true,'fields'=>['source_key'=>[]]],'curatedfaq_public'=>['fields'=>['status'=>[],'display_order'=>[]]]];
