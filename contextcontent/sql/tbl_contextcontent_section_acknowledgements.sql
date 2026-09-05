<?php
$tablename = 'tbl_contextcontent_section_acknowledgements';
$options = array('collate' => 'utf8_general_ci', 'character_set' => 'utf8');
$fields = array(
    'id' => array('type'=>'text','length'=>32,'notnull'=>TRUE),
    'contextcode' => array('type'=>'text','length'=>255,'notnull'=>TRUE),
    'sectionid' => array('type'=>'text','length'=>32,'notnull'=>TRUE),
    'userid' => array('type'=>'text','length'=>64,'notnull'=>TRUE),
    'acknowledgedat' => array('type'=>'timestamp','notnull'=>TRUE)
);
$name = 'tbl_contextcontent_section_ack_idx';
$indexes = array('fields'=>array('contextcode'=>array(),'sectionid'=>array(),'userid'=>array()));
?>
