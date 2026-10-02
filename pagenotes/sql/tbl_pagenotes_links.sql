<?php
/**
 * Database definition for typed note attachments.
 *
 * @author Derek Keats
 * @package pagenotes
 */
$tablename='tbl_pagenotes_links';
$options=array('comment'=>'Typed links between notes and Chisimba resources','collate'=>'utf8mb4_unicode_ci','charset'=>'utf8mb4');
$fields=array(
 'id'=>array('type'=>'text','length'=>32,'notnull'=>TRUE),
 'noteid'=>array('type'=>'text','length'=>32,'notnull'=>TRUE),
 'targettype'=>array('type'=>'text','length'=>48,'notnull'=>TRUE),
 'targetid'=>array('type'=>'text','length'=>255,'notnull'=>TRUE),
 'targetlabel'=>array('type'=>'text','length'=>255,'notnull'=>TRUE),
 'targeturl'=>array('type'=>'text','length'=>1000),
 'createdby'=>array('type'=>'text','length'=>64,'notnull'=>TRUE),
 'datecreated'=>array('type'=>'timestamp','notnull'=>TRUE)
);
$tableIndexes=array(
 'pagenotes_links_primary'=>array('primary'=>TRUE,'fields'=>array('id'=>array())),
 'pagenotes_links_unique'=>array('unique'=>TRUE,'fields'=>array('noteid'=>array(),'targettype'=>array(),'targetid'=>array())),
 'pagenotes_links_target'=>array('fields'=>array('targettype'=>array(),'targetid'=>array()))
);
?>
