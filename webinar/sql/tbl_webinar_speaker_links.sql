<?php
/** One speaker has up to four independently editable links. @author Derek Keats */
$tablename='tbl_webinar_speaker_links';
$options=['type'=>'InnoDB','collate'=>'utf8mb4_unicode_ci','charset'=>'utf8mb4'];
$fields=['id'=>['type'=>'text','length'=>32,'notnull'=>true],'speaker_id'=>['type'=>'text','length'=>32,'notnull'=>true],'slot'=>['type'=>'integer','notnull'=>true],'category'=>['type'=>'text','length'=>24,'notnull'=>true],'label'=>['type'=>'text','length'=>80,'notnull'=>true],'url'=>['type'=>'text','length'=>2048,'notnull'=>true]];
$tableIndexes=['speaker_links_primary'=>['primary'=>true,'fields'=>['id'=>[]]],'speaker_links_slot'=>['unique'=>true,'fields'=>['speaker_id'=>[],'slot'=>[]]]];
