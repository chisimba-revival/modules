<?php
if (empty($GLOBALS['kewl_entry_point_run'])) { die('Direct access denied'); }

class db_faq26_items extends dbTable
{
    public function init($tableName = null, $pearDb = null, $errorCallback = 'globalPearErrorHandler')
    {
        parent::init($tableName !== null ? $tableName : 'tbl_faq26_items', $pearDb, $errorCallback);
    }
}
?>
