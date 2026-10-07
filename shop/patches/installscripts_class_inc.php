<?php
/** Catalogue installation only: verify storage invariants and preserve signing keys.
 * @author Derek Keats <derek@dkeats.com>
 */
if (empty($GLOBALS['kewl_entry_point_run'])) die('No direct access');
class shop_installscripts extends ChisimbaObject
{
    public function postinstall($version = null)
    {
        $db = $this->objEngine->getDbObj(); $db->loadModule('Manager');
        foreach (glob(dirname(__DIR__) . '/sql/tbl_shop_*.sql') as $schema) {
            require $schema;
            $status = $db->queryAll("SHOW TABLE STATUS WHERE Name='" . $tablename . "'", null, MDB2_FETCHMODE_ASSOC);
            if (!is_array($status) || strtolower($status[0]['engine'] ?? '') !== 'innodb') throw new RuntimeException('Shop requires InnoDB: ' . $tablename);
            $existing = $db->queryAll('SHOW INDEX FROM ' . $tablename, null, MDB2_FETCHMODE_ASSOC);
            if (!is_array($existing)) throw new RuntimeException('Shop indexes unavailable');
            foreach ($tableIndexes as $name => $definition) {
                $field = array_key_first($definition['fields']); $found = false;
                foreach ($existing as $index) if ($index['column_name'] === $field && (empty($definition['unique']) || !(int)$index['non_unique'])) $found = true;
                if (!$found) {
                    $result = !empty($definition['unique']) ? $db->manager->createConstraint($tablename, $name . '_unique', $definition) : $db->manager->createIndex($tablename, $name, $definition);
                    if ($result === false || PEAR::isError($result)) throw new RuntimeException('Shop index creation failed: ' . $name);
                }
            }
        }
        $permissions = $this->getObject('permissionservice', 'security');
        $area = $permissions->ensureArea('chisimba', 'shop');
        if (!$area || !$permissions->ensureRight($area, 'manage')) throw new RuntimeException('Shop permissions unavailable');
        $config = $this->getObject('dbsysconfig', 'sysconfig');
        if (!$config->getValue('SHOP_SIGNING_KEY', 'shop')) $config->insertParam('SHOP_SIGNING_KEY', 'shop', bin2hex(random_bytes(32)), 'mod_shop_signing_key');
        $store = $this->getObject('shopstore', 'shop');
        if (!$store->one('settings', 'shop')) $store->add('settings', ['id' => 'shop', 'revision' => 1,
            'settings_json' => json_encode(['enabled' => false, 'terms' => '', 'zones' => []], JSON_THROW_ON_ERROR)]);
    }
}
