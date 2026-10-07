<?php
$tablename='tbl_contactform_limits';
$options=['type'=>'InnoDB','comment'=>'Pseudonymous hourly contact-form submission limits','collate'=>'utf8_general_ci','character_set'=>'utf8'];
$fields=['id'=>['type'=>'text','length'=>64,'notnull'=>true],'bucket'=>['type'=>'integer','notnull'=>true],'total'=>['type'=>'integer','notnull'=>true,'default'=>0]];
$tableIndexes=['contactlimit_primary'=>['primary'=>true,'fields'=>['id'=>[]]]];
