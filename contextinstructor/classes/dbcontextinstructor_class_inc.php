<?php

/**
 * Provides acces to db
 *
 * @author davidwaf
 */
class dbcontextinstructor extends dbTable {

    public function init($tableName = null, $pearDb = null, $errorCallback = 'globalPearErrorHandler') {
        parent::init('tbl_contextinstructor');
    }

    /**
     * returns userid of the main instructor associated with the given contextcode
     * @param <type> $userid
     * @return <type> 
     */
    function getMainInstructor($contextcode) {
        $sql = "select userid from tbl_contextinstructor where contextcode= '$contextcode'";
        $rows = $this->getArray($sql);
        if((is_countable($rows) ? count($rows) : 0) > 0){
            return $rows[0]['userid'];
        }else{
            return FALSE;
        }
    }

    function setMainInstructor($contextcode,$userid){
        //first delete the existing one
        $sql="delete from tbl_contextinstructor where contextcode = '$contextcode'";
        $this->getArray($sql);
        $data=array("contextcode"=>$contextcode,"userid"=>$userid);
        return $this->insert($data);
    }

}

?>
