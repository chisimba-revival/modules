<?php
/** Explicit contribution-schema maintenance; never invoked while rendering pages. */
if (empty($GLOBALS['kewl_entry_point_run'])) die('No direct access');
require_once 'classes/core/chisimbadatabaseschema.php';
class paymentmaintenance extends dbTable
{
    public function init($tableName = null, $pearDb = null, $errorCallback = "globalPearErrorCallback") { parent::init('tbl_payment_service_prices'); }

    /** Return the reviewed plan; check mode does not execute DDL. */
    public function reconcile(bool $apply = false): array
    {
        $schema = new ChisimbaDatabaseSchema($this);
        $identity = $this->selectExactPrepared('SELECT IS_NULLABLE AS nullable FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND COLUMN_NAME=?', ['tbl_payment_service_intents', 'user_id']);
        if (!$identity) throw new RuntimeException('Missing payment identity column');
        if (!$schema->tableExists('tbl_payment_service_prices')) throw new RuntimeException('Missing payment prices table');
        $changes = [];
        if ($identity[0]['nullable'] !== 'YES') $changes[] = 'ALTER TABLE tbl_payment_service_intents MODIFY user_id VARCHAR(25) NULL';
        if (!in_array('vat_minor', $schema->columns('tbl_payment_service_prices'), true)) $changes[] = 'ALTER TABLE tbl_payment_service_prices ADD vat_minor INT NOT NULL DEFAULT 0';
        if ($apply) foreach ($changes as $sql) $schema->executeMigration($sql);
        return $changes;
    }
}
