<?php

class dbrttusers extends dbtable {

    function init($tableName = null, $pearDb = null, $errorCallback = 'globalPearErrorHandler') {
        parent::init('tbl_rtt_users');
    }

    public function saveRttUser($data) {

        $this->insert($data);
    }

    public function authenticateUser($username, $passord) {
        $sql =
                "select * from tbl_rtt_users where userid='$username' and password = '$passord'";
      
        $array = $this->getArray($sql);
        return count($array[0]) > 0 ? true : false;
    }

}

?>
