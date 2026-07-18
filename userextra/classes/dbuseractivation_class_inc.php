<?php
// security check - must be included in all scripts
if(!$GLOBALS['kewl_entry_point_run']) {
    die("You cannot view this page directly");
}
class dbuseractivation extends dbtable {
    private $tablename;
    function init($tableName = null, $pearDb = null, $errorCallback = 'globalPearErrorHandler') {
        parent::init('tbl_userextra_activation');
        $this->tablename='tbl_userextra_activation';
    }

   
    function isUserActivated($userid) {
        $sql=
                "select userid from tbl_userextra_activation where userid = '$userid'";
        $rows=$this->getArray($sql);
        if((is_countable($rows) ? count($rows) : 0) > 0) {
            return TRUE;
        }else {
            return FALSE;
        }
    }


    function addUser($userid){
        $data=array('userid'=>$userid);
        $this->insert($data);
    }

}

?>
