<?php
class dbapomoderator extends dbtable{
    var $tablename = "tbl_context";

    public function init($tableName = null, $pearDb = null, $errorCallback = 'globalPearErrorHandler'){
        parent::init($this->tablename);

    }
    public function getContexts(){
        
    }
}

?>
