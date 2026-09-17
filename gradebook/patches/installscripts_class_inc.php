<?php
/** Gradebook schema repairs for existing installations. @author Derek Keats */
if (empty($GLOBALS['kewl_entry_point_run'])) { die('No direct access'); }

class gradebook_installscripts extends dbtable
{
    public function init($tableName = null, $pearDb = null, $errorCallback = 'globalPearErrorHandler') {}

    /** Repair before the catalogue records the new version; safe to retry. */
    public function preinstall($version = null)
    {
        $admin = $this->getObject('modulesadmin', 'modulecatalogue');
        $table = 'tbl_gradebook_assessment_plan_items';
        $tables = $admin->listDbTables();
        if (!is_array($tables)) {
            throw new RuntimeException('Cannot inspect Gradebook tables for upgrade.');
        }
        // Fresh installs create the complete table from its SQL definition.
        if (!in_array($table, $tables, true)) { return; }

        $columns = $admin->listTblFields($table);
        if (!is_array($columns)) {
            throw new RuntimeException('Cannot inspect Gradebook assessment columns for upgrade.');
        }
        if (in_array('short_name', $columns, true)) { return; }

        // Keep the repair identical to the fresh-install column definition.
        require dirname(__DIR__).'/sql/tbl_gradebook_assessment_plan_items.sql';
        $changes = array('add' => array('short_name' => $fields['short_name']));
        foreach (array(true, false) as $check) {
            $result = $admin->alterTable($table, $changes, $check);
            if ($result !== true && $result !== MDB2_OK) {
                throw new RuntimeException('Cannot add Gradebook assessment short_name column.');
            }
        }
    }
}
