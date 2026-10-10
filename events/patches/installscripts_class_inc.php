<?php
/** Repeatable installation: preserve the signing key across every module update.
 * @author Derek Keats <derek@dkeats.com>
 */
if(empty($GLOBALS['kewl_entry_point_run'])) die('No direct access');
class events_installscripts extends ChisimbaObject
{
    public function postinstall($version=null)
    {
        // Older catalogue adapters may omit declared indexes. Verify constraints here,
        // never on an ordinary request; abort installation if they cannot be enforced.
        $db=$this->objEngine->getDbObj(); $db->loadModule('Manager');
        foreach(glob(dirname(__DIR__).'/sql/tbl_events_*.sql') as $schema) {
            require $schema;
            $existing=$db->queryAll('SHOW INDEX FROM '.$tablename,null,MDB2_FETCHMODE_ASSOC);
            if(!is_array($existing)) throw new RuntimeException('Event index inspection failed');
            foreach($tableIndexes as $name=>$definition) {
                $field=array_key_first($definition['fields']); $found=false;
                foreach($existing as $index) if($index['column_name']===$field && (empty($definition['unique'])||!(int)$index['non_unique'])) $found=true;
                if(!$found) {
                    $result=!empty($definition['unique'])
                        ? $db->manager->createConstraint($tablename,$name.'_unique',$definition)
                        : $db->manager->createIndex($tablename,$name,$definition);
                    if(PEAR::isError($result)||$result===false) throw new RuntimeException('Event constraint creation failed: '.$name.': '.(PEAR::isError($result)?$result->getMessage().' '.$result->getUserInfo():'database failure'));
                }
            }
        }
        $permissions=$this->getObject('permissionservice','security');
        $area=$permissions->ensureArea('chisimba','events');
        if(!$area||!$permissions->ensureRight($area,'manage')) throw new RuntimeException('Event permissions could not be registered');
        $config=$this->getObject('dbsysconfig','sysconfig');
        if(!$config->getValue('EVENTS_SIGNING_KEY','events'))
            $config->insertParam('EVENTS_SIGNING_KEY','events',bin2hex(random_bytes(32)),'mod_events_signing_key');
    }
}
