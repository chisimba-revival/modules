<?php
/** Catalogue-only schema verification; never alter schema on a public request. */
if(empty($GLOBALS['kewl_entry_point_run']))die('No direct access');
class contactform_installscripts extends ChisimbaObject
{
    public function postinstall($version=null)
    {
        $db=$this->objEngine->getDbObj();
        foreach(['messages','limits','review','blocks'] as $suffix){
            $table='tbl_contactform_'.$suffix;
            $rows=$db->queryAll("SHOW TABLE STATUS WHERE Name='".$table."'",null,MDB2_FETCHMODE_ASSOC);
            if(!is_array($rows)||strtolower($rows[0]['engine']??'')!=='innodb')throw new RuntimeException('Contact storage requires InnoDB: '.$table);
        }
    }
}
