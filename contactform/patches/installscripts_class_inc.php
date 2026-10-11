<?php
/** Catalogue-only schema verification; never alter schema on a public request. */
if(empty($GLOBALS['kewl_entry_point_run']))die('No direct access');
class contactform_installscripts extends dbTable
{
    public function postinstall($version=null)
    {
        $admin=$this->getObject('modulesadmin','modulecatalogue');
        foreach(['messages','limits','review','blocks'] as $suffix){
            $table='tbl_contactform_'.$suffix;
            $rows=$this->selectPrepared("SHOW TABLE STATUS WHERE Name='".$table."'");
            if(!is_array($rows)||strtolower($rows[0]['engine']??'')!=='innodb')throw new RuntimeException('Contact storage requires InnoDB: '.$table);
        }
    }
}
