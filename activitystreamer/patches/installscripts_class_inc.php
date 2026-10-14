<?php
/** Preserve legacy numeric authors while accepting current string user IDs. @author Derek Keats */
if(empty($GLOBALS['kewl_entry_point_run']))die('No direct access');
class activitystreamer_installscripts extends dbTable
{
    public function preinstall($version = null)
    {
        $admin=$this->getObject('modulesadmin','modulecatalogue');
        if(!in_array('tbl_activity',$admin->listDbTables(),true))return;
        $columns=$this->selectPrepared("SELECT DATA_TYPE AS kind, CHARACTER_MAXIMUM_LENGTH AS size FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='tbl_activity' AND COLUMN_NAME='createdby'");
        if(!$columns)throw new RuntimeException('Activity author column missing');
        if(in_array($columns[0]['kind'],['varchar','char','text','mediumtext','longtext'],true) && (int)$columns[0]['size']>=50)return;
        $changes=['change'=>['createdby'=>['type'=>'text','length'=>50]]];
        foreach([true,false] as $check)if($admin->alterTable('tbl_activity',$changes,$check)!==true)throw new RuntimeException('Activity author migration failed');
    }
}
