<?php
$tablename = 'tbl_contextcontent_sections';
$options = array('collate' => 'utf8_general_ci', 'character_set' => 'utf8');
$fields = array(
    'id' => array('type'=>'text','length'=>32,'notnull'=>TRUE),
    'contextcode' => array('type'=>'text','length'=>255,'notnull'=>TRUE),
    'title' => array('type'=>'text','length'=>255,'notnull'=>TRUE),
    'introduction' => array('type'=>'clob'),
    'sectionorder' => array('type'=>'integer','length'=>11,'notnull'=>TRUE,'default'=>0),
    'visibility' => array('type'=>'text','length'=>1,'notnull'=>TRUE,'default'=>'Y'),
    'creatorid' => array('type'=>'text','length'=>64,'notnull'=>TRUE),
    'datecreated' => array('type'=>'timestamp','notnull'=>TRUE),
    'modifierid' => array('type'=>'text','length'=>64),
    'datemodified' => array('type'=>'timestamp')
);
$name = 'tbl_contextcontent_sections_idx';
$indexes = array('fields'=>array('contextcode'=>array(),'sectionorder'=>array(),'visibility'=>array()));
?>
