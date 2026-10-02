<?php
/**
 * Database definition for canonical scoped notes.
 *
 * @author Derek Keats
 * @package pagenotes
 */
$tablename='tbl_pagenotes_items';
$options=array('comment'=>'Canonical scoped notes','collate'=>'utf8mb4_unicode_ci','charset'=>'utf8mb4');
$fields=array(
 'id'=>array('type'=>'text','length'=>32,'notnull'=>TRUE),
 'scopetype'=>array('type'=>'text','length'=>16,'notnull'=>TRUE),
 'scopeid'=>array('type'=>'text','length'=>255,'notnull'=>TRUE),
 'ownerid'=>array('type'=>'text','length'=>64,'notnull'=>TRUE),
 'title'=>array('type'=>'text','length'=>255,'notnull'=>TRUE),
 'body'=>array('type'=>'clob'),
 'isarchived'=>array('type'=>'integer','length'=>1,'default'=>0,'notnull'=>TRUE),
 'datecreated'=>array('type'=>'timestamp','notnull'=>TRUE),
 'datemodified'=>array('type'=>'timestamp','notnull'=>TRUE)
);
$tableIndexes=array(
 'pagenotes_notes_primary'=>array('primary'=>TRUE,'fields'=>array('id'=>array())),
 'pagenotes_notes_scope'=>array('fields'=>array('scopetype'=>array(),'scopeid'=>array(),'isarchived'=>array(),'datemodified'=>array())),
 'pagenotes_notes_owner'=>array('fields'=>array('ownerid'=>array()))
);
?>
