<?php
/** Explicit composition-schema maintenance; preserves existing page content. */
if (empty($GLOBALS['kewl_entry_point_run'])) die('No direct access');
require_once 'classes/core/chisimbadatabaseschema.php';
class sitepagesmaintenance extends dbTable
{
    public function init($tableName = null, $pearDb = null, $errorCallback = "globalPearErrorCallback") { parent::init('tbl_sitepages'); }

    public function reconcile(bool $apply = false): array
    {
        $schema = new ChisimbaDatabaseSchema($this);
        if (!$schema->tableExists('tbl_sitepages')) throw new RuntimeException('Install sitepages first');
        $changes = [];
        if (!in_array('composition_json', $schema->columns('tbl_sitepages'), true)) $changes[] = 'ALTER TABLE tbl_sitepages ADD COLUMN composition_json LONGTEXT NULL';
        $engine = $this->selectExactPrepared('SELECT ENGINE AS engine FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=?', ['tbl_sitepages']);
        if (strcasecmp($engine[0]['engine'], 'InnoDB') !== 0) $changes[] = 'ALTER TABLE tbl_sitepages ENGINE=InnoDB';
        if ($apply) foreach ($changes as $sql) $schema->executeMigration($sql);
        return $changes;
    }
}
