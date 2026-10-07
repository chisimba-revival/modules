<?php
$tablename='tbl_contactform_blocks';
$options=['type'=>'InnoDB','comment'=>'Explicit contact sender blocks, keyed hashes only','collate'=>'utf8_general_ci','character_set'=>'utf8'];
$fields=['id'=>['type'=>'text','length'=>64,'notnull'=>true],'datecreated'=>['type'=>'timestamp']];
$tableIndexes=['contactblocks_primary'=>['primary'=>true,'fields'=>['id'=>[]]]];
