<?php
/** Preserve guest identity and immutable VAT through normal catalogue updates. */
if (empty($GLOBALS['kewl_entry_point_run'])) die('No direct access');
class payment_service_installscripts extends ChisimbaObject
{
    public function preinstall($version = null)
    {
        $tables = $this->getObject('modulesadmin', 'modulecatalogue')->listDbTables();
        if (!is_array($tables)) throw new RuntimeException('Payment schema inspection failed');
        $xml = simplexml_load_file(dirname(__DIR__).'/sql/sql_updates.xml', 'SimpleXMLElement', LIBXML_NONET);
        $updates = $xml ? $xml->xpath('/updates/update[version="1.034"]') : false;
        if (!$updates) throw new RuntimeException('Payment reconciliation migration missing');
        foreach ($updates as $update) {
            if (!in_array((string)$update->table, $tables, true)) continue;
            foreach ($update->SQL as $statement) {
                $result = $this->objEngine->getDbObj()->exec((string)$statement);
                if ($result === false || PEAR::isError($result)) throw new RuntimeException('Payment schema reconciliation failed');
            }
        }
    }
    public function postinstall($version = null) { $this->preinstall($version); }
}
// The engine resolves hooks using the literal module ID, which contains a hyphen.
class_alias(payment_service_installscripts::class, 'payment-service_installscripts');
