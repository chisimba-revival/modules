<?php
/** Register host permissions and enforce stable profile identities.
 * @author Derek Keats <derek@dkeats.com>
 */
if(empty($GLOBALS['kewl_entry_point_run'])) die('No direct access');
class host_service_installscripts extends dbTable
{
    public function postinstall($version=null)
    {
        $admin=$this->getObject('modulesadmin','modulecatalogue'); $indexes=$this->selectPrepared('SHOW INDEX FROM tbl_host_service_profiles');
        if(!is_array($indexes)) throw new RuntimeException('Cannot inspect host indexes');
        $found=false; foreach($indexes as $index) if($index['column_name']==='id'&&!(int)$index['non_unique']) $found=true;
        if(!$found) { $result=$admin->createConstraint('tbl_host_service_profiles','host_service_identity',['unique'=>true,'fields'=>['id'=>[]]]); if($result===false) throw new RuntimeException('Cannot create host identity constraint'); }
        $p=$this->getObject('permissionservice','security'); $area=$p->ensureArea('chisimba','host-service');
        if(!$area||!$p->ensureRight($area,'manage')) throw new RuntimeException('Cannot register host permissions');
    }
}
// The engine resolves hooks using the literal module ID.
class_alias(host_service_installscripts::class,'host-service_installscripts');
