<?php

/**
 *
 */
class dbuseractivity extends dbtable {

    function init($tableName = null, $pearDb = null, $errorCallback = 'globalPearErrorHandler') {
        parent::init('tbl_useractivity');
        $this->objUser = $this->getObject('user', 'security');
    }

    function getUserActivityByModule($startdate, $enddate, $module, $studentsonly, $usersInContext, $contextcode) {
        $results = array();
        foreach ($usersInContext as $user) {
            $sql =
                    "select count(userid) as accesscount,createdon from tbl_useractivity
        where  (createdon between '$startdate' and '$enddate')
        and contextcode ='$contextcode' and userid='" . $user['auth_user_id'] . "'";

            if ($module != 'all') {
                $sql.=" and module='$module' ";
            }

            $sql.="group by userid order by accesscount";


            $data = $this->getArray($sql);
            $activity = array();
            $activity['userid'] = $user['auth_user_id'];
            if ((is_countable($data) ? count($data) : 0) > 0) {
                $activity['accesscount'] = $data[0]['accesscount'];
                $activity['lastaccess'] = $data[0]['createdon'];
            } else {
                $activity['accesscount'] = '0';
                $activity['lastaccess'] = 'Never';
            }
            $results[] = $activity;
        }
        return $results;
    }

}

?>
