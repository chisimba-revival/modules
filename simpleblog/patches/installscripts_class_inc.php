<?php
/** Repeatable forward reconciliation through the catalogue's installation hooks. */
if (empty($GLOBALS['kewl_entry_point_run'])) die('No direct access');
class simpleblog_installscripts extends ChisimbaObject
{
    public function preinstall($version = null)
    {
        $tables = $this->getObject('modulesadmin', 'modulecatalogue')->listDbTables();
        if (!is_array($tables)) throw new RuntimeException('Publishing schema inspection failed');
        if (!in_array('tbl_simpleblog_posts', $tables, true)) return;
        // Legacy patch::applyUpdates reads <data>, not raw <SQL>. Execute only
        // this reviewed forward union; never replay historical migrations.
        $xml = simplexml_load_file(dirname(__DIR__).'/sql/sql_updates.xml', 'SimpleXMLElement', LIBXML_NONET);
        $statements = $xml ? $xml->xpath('/updates/update[version="0.075"]/SQL') : false;
        if (!$statements) throw new RuntimeException('Publishing reconciliation migration missing');
        foreach ($statements as $statement) {
            $result = $this->objEngine->getDbObj()->exec((string)$statement);
            if ($result === false || PEAR::isError($result)) throw new RuntimeException('Publishing schema reconciliation failed');
        }
    }
    public function postinstall($version = null)
    {
        $this->preinstall($version);
        // Preserve capability definitions from the security cleanup. Never grant
        // memberships or publishing access as part of a schema upgrade.
        $permissions = $this->getObject('permissionservice', 'security');
        $area = $permissions->ensureArea('chisimba', 'simpleblog');
        foreach (['personal_publish', 'site_publish', 'site_manage'] as $name) {
            if (!$permissions->ensureRight($area, $name)) {
                throw new RuntimeException('Publishing permission definition failed');
            }
        }
    }
}
