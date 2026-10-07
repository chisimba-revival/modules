<?php
$tablename='tbl_contactform_review';
$options=['type'=>'InnoDB','comment'=>'Reversible contact inbox moderation','collate'=>'utf8_general_ci','character_set'=>'utf8'];
$fields=['id'=>['type'=>'text','length'=>32,'notnull'=>true],'folder'=>['type'=>'text','length'=>10,'notnull'=>true],'reason'=>['type'=>'text','length'=>40,'notnull'=>true],'actor'=>['type'=>'text','length'=>100],'datemodified'=>['type'=>'timestamp']];
$tableIndexes=['contactreview_primary'=>['primary'=>true,'fields'=>['id'=>[]]]];
