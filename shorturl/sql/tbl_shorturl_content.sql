<?php
$tablename = 'tbl_shorturl_content';
$options = ['comment'=>'Persistent page/post paths and retained aliases','collate'=>'utf8_general_ci','character_set'=>'utf8'];
$fields = [
 'id'=>['type'=>'text','length'=>32,'notnull'=>1],
 'slug'=>['type'=>'text','length'=>190,'notnull'=>1],
 'module_id'=>['type'=>'text','length'=>32,'notnull'=>1],
 'content_id'=>['type'=>'text','length'=>32,'notnull'=>1],
 'canonical'=>['type'=>'integer','length'=>1,'notnull'=>1,'default'=>1],
];
$tableIndexes = [
 'shorturl_content_pk'=>['primary'=>true,'fields'=>['id'=>[]]],
 'shorturl_content_slug'=>['unique'=>true,'fields'=>['slug'=>[]]],
 'shorturl_content_owner'=>['fields'=>['module_id'=>[],'content_id'=>[]]],
];
