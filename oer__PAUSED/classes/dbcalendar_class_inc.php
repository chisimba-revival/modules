<?php

/**
 * This class provides the interface to the database for managing section calendar
 *
 * @author davidwaf
 */
class dbcalendar extends dbTable {

    private $tableName = 'tbl_oer_calendar';

    function init($tableName = null, $pearDb = null, $errorCallback = 'globalPearErrorHandler') {
        parent::init($this->tableName);
    }

    /**
     * returns the calendar nodes of the sections
     * @param type $productId
     * @return type 
     */
    function getCurriculum($productId) {
        $sql =
                "select * from tbl_oer_calendar where product_id = '$productId'";
        $result = $this->getArray($sql);

        if ((is_countable($result) ? count($result) : 0) > 0) {
            return $result[0];
        } else {
            return NULL;
        }
    }

    /**
     * Creates a new calendar record
     * @param type $data
     * @return type 
     */
    function addCalendar($data) {
        return $this->insert($data);
    }

}

?>
