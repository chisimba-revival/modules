<?php
$tablename = 'tbl_offlineassessment_types';
$options = array('comment' => 'Editable offline assessment types', 'collate' => 'utf8_general_ci', 'character_set' => 'utf8');
$fields = array(
    'id' => array('type'=>'text','length'=>32,'notnull'=>TRUE),
    'name' => array('type'=>'text','length'=>255,'notnull'=>TRUE),
    'sort_order' => array('type'=>'integer','length'=>11,'notnull'=>TRUE,'default'=>0),
    'status' => array('type'=>'text','length'=>16,'notnull'=>TRUE,'default'=>'active'),
    'is_seed' => array('type'=>'text','length'=>1,'notnull'=>TRUE,'default'=>'N'),
    'created_by' => array('type'=>'text','length'=>255,'notnull'=>TRUE),
    'date_created' => array('type'=>'timestamp','notnull'=>TRUE),
    'date_updated' => array('type'=>'timestamp','notnull'=>TRUE)
);
$tableIndexes = array(
    'offlineassessment_types_primary'=>array('primary'=>TRUE,'fields'=>array('id'=>array())),
    'offlineassessment_types_name'=>array('unique'=>TRUE,'fields'=>array('name'=>array()))
);
?>