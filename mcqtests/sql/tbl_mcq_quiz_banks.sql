<?php
/** Chapter bank and quiz selection policy. @author Derek Keats */
$tablename='tbl_mcq_quiz_banks';
$options=['comment'=>'Chapter quiz bank links','collate'=>'utf8mb4_unicode_ci','charset'=>'utf8mb4'];
$fields=['id'=>['type'=>'text','length'=>32,'notnull'=>true],'bankid'=>['type'=>'text','length'=>32,'notnull'=>true],'contextcode'=>['type'=>'text','length'=>255,'notnull'=>true],'chapterid'=>['type'=>'text','length'=>32,'notnull'=>true],'bank_count'=>['type'=>'integer','notnull'=>true],'draw_count'=>['type'=>'integer','notnull'=>true],'selection'=>['type'=>'text','length'=>20,'notnull'=>true]];
$tableIndexes=['mcq_quiz_bank_primary'=>['primary'=>true,'fields'=>['id'=>[]]],'mcq_quiz_bank_chapter'=>['fields'=>['contextcode'=>[],'chapterid'=>[]]]];
