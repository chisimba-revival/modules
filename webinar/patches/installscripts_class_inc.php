<?php
/** Define a staff capability without granting it to any account or group. @author Derek Keats */
if(empty($GLOBALS['kewl_entry_point_run']))die('No direct access');
class webinar_installscripts extends dbTable
{
    public function init($tableName=null,$pearDb=null,$errorCallback='globalPearErrorHandler'){}
    public function postinstall($version=null)
    {
        $this->getObject('webinareditstore','webinar')->prepareSchema();
        $p=$this->getObject('permissionservice','security');$area=$p->ensureArea('chisimba','webinar');
        if(!$p->ensureRight($area,'manage'))throw new RuntimeException('Webinar capability setup failed');
    }
}
