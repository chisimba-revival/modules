<?php

/**
 * Class the records the pages a user has visited.
 *
 *
 * PHP version 5
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the
 * Free Software Foundation, Inc.,
 * 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 *
 * @category  Chisimba
 * @package   contextcontent
 * @author    Paul Mungai <paulwando@gmail.com>
 * @copyright @2010 University of the Witwatersrand
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt The GNU General Public License
 * @version   $Id: db_contextcontent_titles_class_inc.php 11385 2008-11-07 00:52:41Z charlvn $
 * @link      http://avoir.uwc.ac.za
 * @see       core
 */
// security check - must be included in all scripts
if (!
        /**
         * Description for $GLOBALS
         * @global unknown $GLOBALS['kewl_entry_point_run']
         * @name   $kewl_entry_point_run
         */
        $GLOBALS['kewl_entry_point_run']) {
    die("You cannot view this page directly");
}
// end security check

/**
 * Class the records the pages a user has visited.
 *
 * It doesn't contain the content of pages, just the index to track which pages
 * are translations of each other.
 *
 * @category  Chisimba
 * @package   contextcontent
 * @author    Paul Mungai <paulwando@gmail.com>
 * @copyright @2010 University of the Witwatersrand
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt The GNU General Public License
 * @version   Release: @package_version@
 * @link      http://avoir.uwc.ac.za
 * @see       core
 */
class db_contextcontent_activitystreamer extends dbtable {
    /** Dependencies and state initialised by this component. */
    public $contextCode;
    public $objContentChapter;
    public $objContentPages;
    public $objContentTitles;
    public $objContext;
    public $objFiles;
    public $objUser;
    public $sessionId;


    /**
     * Constructor
     */
    public function init($tableName = null, $pearDb = null, $errorCallback = 'globalPearErrorHandler') {
        parent::init('tbl_contextcontent_activitystreamer');
        $this->objUser = $this->getObject('user', 'security');
        //Load content pages
        $this->objContentTitles = $this->getObject('db_contextcontent_titles');
        $this->objContentChapter = $this->getObject('db_contextcontent_contextchapter');
        $this->objContentPages = $this->getObject('db_contextcontent_pages');
        $this->objFiles = $this->getObject('dbfile', 'filemanager');
        // Load Context Object
        $this->objContext = $this->getObject('dbcontext', 'context');

        // Store Context Code
        $this->contextCode = $this->objContext->getContextCode();
        $this->sessionId = session_id();
    }

    /**
     * Method to add a record.
     *
     * @access public
     * @param string $userId User ID
     * @param string $sessionid session ID
     * @param string $contextItemId context Item ID
     * @param string $contextCode context Code
     * @param string $moduleCode module Code
     * @param string $datecreated date created
     * @param string $pageorchapter record whether its a context page or chapter
     * @param string $description description of activity
     * @param string $sessionstarttime session start time
     * @param string $sessionendtime session end time
     */
    public function addRecord($userId, $sessionid, $contextItemId, $contextCode, $modulecode, $datecreated=Null, $pageorchapter=NULL, $description=NULL, $sessionstarttime=NULL, $sessionendtime=NULL) {
        $row = array();
        if ($contextItemId == null || $contextItemId == "") {
            $contextItemId = "x";
        }
        $row['userid'] = $userId;
        $row['contextcode'] = $contextCode;
        $row['contextitemid'] = $contextItemId;
        $row['datecreated'] = date('Y-m-d H:i:s');
        $row['sessionid'] = $this->sessionId;
        $row['modulecode'] = $modulecode;
        $row['pageorchapter'] = $pageorchapter;
        if ($pageorchapter == "chapter") {
            $row['description'] = $this->objContentChapter->getContextChapterTitle($contextItemId);
        } else {
            $row['description'] = $description;
        }
        $row['starttime'] = date('Y-m-d H:i:s');
        $row['endtime'] = $sessionendtime;
        return $this->insert($row);
    }

    /**
     * Checks if record exists.
     *
     * @access public
     * @param string $id The activitystreamer id.
     * @return boolean
     */
    public function idExists($id) {
        return $this->valueExists('id', $id);
    }

    /**
     * Method to check if record exists according to userId, contextItemId and sessionid.
     *
     * @access public
     * @param string $userId User ID
     * @param string $contextItemId Context Item Id
     * @param string $contextCode Context Code
     * @param string $sessionId Session Id
     * @return TRUE
     */
    public function getRecord($userId, $contextItemId, $sessionid=Null) {
        if (empty($sessionid)) {
            $where = "WHERE userid = '$userId' AND contextitemid = '$contextItemId'";
        } else {
            $where = "WHERE userid = '$userId' AND contextitemid = '$contextItemId' AND sessionid = '$sessionid'";
        }
        $results = $this->getAll($where);
        if (isset($results[0]['id'])) {
            return TRUE;
        } else {
            return FALSE;
        }
    }

    /**
     * Method to fetch record
     *
     * @access public
     * @param string $Id ID
     * @param string $contextItemId Context Item Id
     * @param string $contextCode Context Code
     * @return TRUE
     */

    /** Return the most recent ContextContent page visit for one user in one context. */
    public function getLatestPageVisit($userId, $contextCode)
    {
        $sql = "SELECT contextitemid, description, datecreated
                FROM tbl_contextcontent_activitystreamer
                WHERE userid = ?
                  AND contextcode = ?
                  AND modulecode = 'contextcontent'
                  AND pageorchapter = 'page'
                  AND contextitemid <> 'x'
                ORDER BY datecreated DESC, puid DESC LIMIT 1";
        $rows = $this->selectPrepared($sql,[$userId,$contextCode]);
        return (!is_array($rows) || count($rows) === 0) ? FALSE : $rows[0];
    }

    /** Count distinct ContextContent pages visited by one user in one context. */
    public function countVisitedPages($userId, $contextCode)
    {
        $sql = "SELECT COUNT(DISTINCT contextitemid) AS visited
                FROM tbl_contextcontent_activitystreamer
                WHERE userid = ?
                  AND contextcode = ?
                  AND modulecode = 'contextcontent'
                  AND pageorchapter = 'page'
                  AND contextitemid <> 'x'";
        $rows = $this->selectPrepared($sql,[$userId,$contextCode]);
        return (!is_array($rows) || count($rows) === 0) ? 0 : (int) $rows[0]['visited'];
    }

    public function getRecordById($Id) {
        $where = "WHERE id = '$Id'";
        $results = $this->getAll($where);
        if (isset($results[0])) {
            return $results[0];
        } else {
            return FALSE;
        }
    }

    /**
     * Method to check if record exists according to userId, contextItemId and sessionid.
     *
     * @access public
     * @param string $userId User ID
     * @param string $contextItemId Context Item Id
     * @param string $contextCode Context Code
     * @return TRUE
     */
    public function checkRecord($userId, $contextItemId, $contextCode) {
        $where = "WHERE userid = '$userId' AND contextitemid = '$contextItemId' AND contextcode = '$contextCode'";
        $results = $this->getAll($where);
        if (isset($results[0]['id'])) {
            return TRUE;
        } else {
            return FALSE;
        }
    }

    /**
     * Method to fetch active context members.
     *
     * @access public
     * @param string $contextCode Context Code
     * @return Array
     */
    public function getActiveMembers($contextCode, $startdate, $enddate) {
        $getDistinct =
                "SELECT distinct userid  FROM tbl_contextcontent_activitystreamer WHERE
        contextcode = '$contextCode' ";

        if ($startdate && $enddate) {
            $getDistinct.="   and (datecreated between '$startdate' and '$enddate')";
        }


        $results = $this->getArray($getDistinct);
        if ((is_countable($results) ? count($results) : 0) == 0) {
            return FALSE;
        } else {
            return $results;
        }
    }

    public function getLoginCount($contextCode, $startdate, $enddate) {
        $sql =
                "SELECT count(userid) as logincount,userid  FROM tbl_contextcontent_activitystreamer WHERE
        contextcode = '$contextCode'";

        if ($startdate && $enddate) {
            $sql.="   and (datecreated between '$startdate' and '$enddate')";
        }

        $sql.=" group by userid order by logincount desc";
        $results = $this->getArray($sql);
        if ((is_countable($results) ? count($results) : 0) == 0) {
            return FALSE;
        } else {

            return $results;
        }
    }

    /**
     * Method to fetch distinct user sessions.
     *
     * @access public
     * @param string $userId User ID
     * @return Array
     */
    public function getUserSessions($userId) {
        $getDistinct = "SELECT distinct sessionid  FROM tbl_contextcontent_activitystreamer WHERE userid = '$userId'";
        $results = $this->getArray($getDistinct);
        if ((is_countable($results) ? count($results) : 0) == 0) {
            return FALSE;
        } else {
            return $results[0];
        }
    }

    /**
     * Method to fetch all user sessions.
     *
     * @access public
     * @param string $userId User ID
     * @return Array
     */
    public function getAllUserSessions($userId, $startdate, $enddate) {
        $getDistinct = "SELECT *  FROM tbl_contextcontent_activitystreamer WHERE userid = '$userId'";
        if ($startdate && $enddate) {
            $getDistinct.=" and datecreated between '$startdate' and '$enddate')";
        }
        $results = $this->getArray($getDistinct);

        //print_r($results);
        //die();
        if ((is_countable($results) ? count($results) : 0) == 0) {
            return FALSE;
        } else {
            return $results;
        }
    }

    /**
     * Determine whether activity-stream records exist for a session.
     *
     * This method was formerly named getSession(), which collided with the
     * ChisimbaObject session API on PHP 8. The distinct name preserves the
     * database-specific meaning and avoids overriding the framework method.
     *
     * @access public
     * @param string $sessionId Session identifier
     * @return bool TRUE when at least one matching record exists
     */
    public function hasSessionRecords($sessionId) {
        $where = "WHERE sessionid = '$sessionId'";
        $results = $this->getAll($where);
        if (isset($results[0])) {
            return TRUE;
        } else {
            return FALSE;
        }
    }

    /**
     * Method to fetch the first record for the session using min start time.
     *
     * @access public
     * @param string $sessionId session Id
     * @return Array
     */
    public function getMinSessionTime($sessionId) {
        $getDistinct = "SELECT id, endtime, min(starttime) as minstarttime  FROM tbl_contextcontent_activitystreamer WHERE sessionid = '$sessionId'";
        $results = $this->getArray($getDistinct);
        if ((is_countable($results) ? count($results) : 0) == 0) {
            return FALSE;
        } else {
            return $results[0];
        }
    }

    /**
     * Method to fetch the last record for the session using max start time.
     *
     * @access public
     * @param string $sessionId session Id
     * @return Array
     */
    public function getMaxSessionTime($sessionId) {
        $getDistinct = "SELECT id, endtime, max(starttime) as maxstarttime  FROM tbl_contextcontent_activitystreamer WHERE sessionid = '$sessionId'";
        $results = $this->getArray($getDistinct);
        if ((is_countable($results) ? count($results) : 0) == 0) {
            return FALSE;
        } else {
            return $results[0];
        }
    }

    /**
     * Method to Compute session duration.
     *
     * @access public
     * @param string $sessionId session Id
     * @return Array
     */
    public function computeSessionDuration($sessionId) {
        $minData = $this->getMinSessionTime($sessionId);
        $minTime = $minData["minstarttime"];
        $maxData = $this->getMaxSessionTime($sessionId);
        if (empty($maxData['endtime'])) {
            $maxTime = $maxData["maxstarttime"];
        } else {
            $maxTime = $maxData["endtime"];
        }
        $minmaxtime = array();
        $minmaxtime['mintime'] = $minTime;
        $minmaxtime['maxtime'] = $maxTime;
        //Create dateTime Objects
        /* $minTime = date_create($minTime);
          $maxTime = date_create($maxTime); */
        $dateDiff = $this->date_diff($minTime, $maxTime);
        $minmaxtime['duration'] = $dateDiff;
        return $minmaxtime;
    }

    /**
     * Method to Compute Time taken in a transaction.
     *
     * @access public
     * @param string $Id Id
     * @return Array
     */
    public function computeTimeTakenPerTrans($rowdata) {
        $minTime = $rowdata["starttime"];
        if (empty($rowdata['endtime'])) {
            $maxTime = $rowdata["starttime"];
        } else {
            $maxTime = $rowdata["endtime"];
        }
        $minmaxtime = array();
        $minmaxtime['mintime'] = $minTime;
        $minmaxtime['maxtime'] = $maxTime;
        //Create dateTime Objects
        /* $minTime = date_create($minTime);
          $maxTime = date_create($maxTime); */

        $dateDiff = $this->date_diff($minTime, $maxTime);
        $minmaxtime['duration'] = $dateDiff;
        return $minmaxtime;
    }

    public function date_diff($start, $end="NOW") {
        $sdate = strtotime($start);
        $edate = strtotime($end);

        $time = $edate - $sdate;
        if ($time >= 0 && $time <= 59) {
            // Seconds
            $timeshift = $time . ' seconds ';
        } elseif ($time >= 60 && $time <= 3599) {
            // Minutes + Seconds
            $pmin = ($edate - $sdate) / 60;
            $premin = explode('.', $pmin);

            $presec = $pmin - $premin[0];
            $sec = $presec * 60;

            $timeshift = $premin[0] . ' min ' . round($sec, 0) . ' sec ';
        } elseif ($time >= 3600 && $time <= 86399) {
            // Hours + Minutes
            $phour = ($edate - $sdate) / 3600;
            $prehour = explode('.', $phour);

            $premin = $phour - $prehour[0];
            $premin = $premin * 60;
            //Check if . exists in string

            $secExist = strpos('.', $premin);
            if ($secExist === false) {
                $min = 0;
                $sec = 0;
            } else {
                $min = explode('.', $premin);
                $presec = '0.' . $min[1];
                $sec = $presec * 60;
            }
            $timeshift = $prehour[0] . ' hrs ' . $min[0] . ' min ' . round($sec, 0) . ' sec ';
        } elseif ($time >= 86400) {
            // Days + Hours + Minutes
            $pday = ($edate - $sdate) / 86400;
            $preday = explode('.', $pday);

            $phour = $pday - $preday[0];
            $prehour = explode('.', $phour * 24);

            $premin = ($phour * 24) - $prehour[0];
            $min = explode('.', $premin * 60);

            $presec = '0.' . $min[1];
            $sec = $presec * 60;

            $timeshift = $preday[0] . ' days ' . $prehour[0] . ' hrs ' . $min[0] . ' min ' . round($sec, 0) . ' sec ';
        }
        return $timeshift;
    }

    /**
     * Method to compute the total time taken in contextcontent by a user.
     *
     * @access public
     * @param string $userId User ID
     * @return string
     */
    public function computeUserSessionsDuration($userId, $startdate, $enddate) {
        $allRecords = $this->getAllUserSessions($userId, $startdate, $enddate);
        $totalDuration = "0";
        $sumHrs = 0;
        $sumMin = 0;
        $sumSec = 0;
        $count = 0;
        foreach ($allRecords as $thisrecord) {
            if ($thisrecord["userid"] == $userId) {
                $count++;
            }
        }

        $totalDuration = "$count logins";

        return $totalDuration;


        foreach ($allRecords as $thisrecord) {
            if ($thisrecord["userid"] == $userId) {
                $tempMin = 0;
                $tempHrs = 0;
                $timeTaken = $this->computeTimeTakenPerTrans($thisrecord);
                $hrsStr = "hrs";
                //Check if str hrs exists using php strpos function
                $hrsExist = strpos($timeTaken["duration"], $hrsStr);
                if ($hrsExist === false) {
                    $hrs = 0;
                    $minStr = "min";
                    $minExist = strpos($timeTaken["duration"], $minStr);
                    if ($minExist === false) {
                        $min = 0;
                        $secStr = "sec";
                        $secExist = strpos($timeTaken["duration"], $secStr);
                        if ($secExist === false) {
                            $sec = 0;
                        } else {
                            $seconds = explode(" sec ", $timeTaken["duration"]);
                            $sec = $seconds[0];
                        }
                    } else {
                        $minutes = explode(" min ", $timeTaken["duration"]);
                        $min = $minutes[0];
                        $secStr = "sec";
                        $secExist = strpos($timeTaken["duration"], $secStr);
                        if ($secExist === false) {
                            $sec = 0;
                        } else {
                            $seconds = explode(" sec ", $minutes[1]);
                            $sec = $seconds[0];
                        }
                    }
                } else {
                    $hours = explode(" hrs ", $timeTaken["duration"]);
                    $hrs = $hours[0];
                    $minStr = "min";
                    $minExist = strpos($timeTaken["duration"], $minStr);
                    if ($minExist === false) {
                        $min = 0;
                        $secStr = "sec";
                        $secExist = strpos($timeTaken["duration"], $secStr);
                        if ($secExist === false) {
                            $sec = 0;
                        } else {
                            $seconds = explode(" sec ", $hours[1]);
                            $sec = $seconds[0];
                        }
                    } else {
                        $minutes = explode(" min ", $hours[1]);
                        $min = $minutes[0];
                        $secStr = "sec";
                        $secExist = strpos($timeTaken["duration"], $secStr);
                        if ($secExist === false) {
                            $sec = 0;
                        } else {
                            $seconds = explode(" sec ", $minutes[1]);
                            $sec = $seconds[0];
                        }
                    }
                }
                //$sec = explode(" sec ",$min[1]);
                //$sec = $sec[0];
                //Add seconds
                $sumSec = $sumSec + $sec;
                //Ifsum Seconds is greater than 60, add to min
                while ($sumSec > 60) {
                    $tempMin = $tempMin + 1;
                    $sumSec = $sumSec - 60;
                }
                //Add minutes
                $sumMin = $sumMin + $min + $tempMin;
                //Ifsum Seconds is greater than 60, add to min
                while ($sumMin > 60) {
                    $tempHrs = $tempHrs + 1;
                    $sumMin = $sumMin - 60;
                }
                //Add only when tempHrs have increased
                if ($tempHrs > 0)
                    $sumHrs = $sumHrs + $tempHrs;
            }
        }
        if ($sumHrs > 1) {
            $totalDuration = $sumHrs . " Hrs " . $sumMin . " Min " . $sumSec . " Sec";
        } else {
            $totalDuration = $sumHrs . " Hr " . $sumMin . " Min " . $sumSec . " Sec";
        }
        return $totalDuration;
    }

    /**
     * Method to compute time taken by each active context member.
     *
     * @access public
     * @param string $contextCode Context Code
     * @return Array
     */
    public function getTimeTakenByEachMember($contextCode, $startdate, $enddate) {
        $activeMembers = $this->getLoginCount($contextCode, $startdate, $enddate);

        $userData = array();
        foreach ($activeMembers as $member) {
            $thisArray = array();
            $duration = $member['logincount']; // $this->computeUserSessionsDuration($member['userid'], $startdate, $enddate);
            $username = $this->objUser->username($member['userid']);
            $thisArray['userid'] = $member['userid'];
            $thisArray['username'] = $username;
            $thisArray['duration'] = $duration;
            $userData[] = $thisArray;
        }

        return $userData;
    }

    /**
     * Update a record
     * @param string $id ID
     * @param string $start The start date
     * @param string $longdescription The long description
     * -- @param string $userId The user ID
     */
    function updateSingle($id) {
        $userid = $this->objUser->userId();
        $this->update("id", $id, array(
            'endtime' => date('Y-m-d H:i:s')
        ));
    }

    /**
     * Method to retrieve a record id according to userId, contextItemId and sessionid.
     *
     * @access public
     * @param string $userId User ID
     * @param string $contextItemId Context Item Id
     * @param string $contextCode Context Code
     * @return string Record ID
     */
    public function getRecordId($userId, $contextItemId, $sessionid) {
        $where = "WHERE userid = '$userId' AND contextitemid = '$contextItemId' AND sessionid = '$sessionid' AND endtime is NULL";
        $results = $this->getAll($where);
        if (!empty($results)) {
            if (empty($results[0]['endtime']))
                return $results[0]['id'];
            else
                return FALSE;
        } else {
            return FALSE;
        }
    }

    /**
     * Method to get a content page
     * @param string $pageId Record Id of the Page
     * @param string $contextCode Context the Page is In
     * @return array Details of the Page, FALSE if does not exist
     * @access public
     */
    public function getPage($pageId, $contextCode) {
        $sql = 'SELECT tbl_contextcontent_order.id, tbl_contextcontent_order.chapterid, tbl_contextcontent_order.parentid,tbl_contextcontent_pages.scorm, tbl_contextcontent_pages.menutitle, pagecontent, headerscripts, lft, rght, tbl_contextcontent_pages.id as pageid, tbl_contextcontent_order.titleid, isbookmarked
        FROM tbl_contextcontent_order
        INNER JOIN tbl_contextcontent_titles ON (tbl_contextcontent_order.titleid = tbl_contextcontent_titles.id)
        INNER JOIN tbl_contextcontent_pages ON (tbl_contextcontent_pages.titleid = tbl_contextcontent_titles.id AND original=\'Y\')
        WHERE tbl_contextcontent_order.id=\'' . $pageId . '\' AND contextcode=\'' . $contextCode . '\'
        ORDER BY lft LIMIT 1';

        $results = $this->getArray($sql);

        if ((is_countable($results) ? count($results) : 0) == 0) {
            return FALSE;
        } else {
            return $results[0];
        }
    }

    /**
     * Return a single record
     * @param string $contextcode Context Code
     * @return array The values
     */
    function getContextLogs($contextcode, $where) {

        return $this->getAll("WHERE contextcode='" . $contextcode . "'" . $where);
    }

    /* Returns two datetime values by calculating time randomly from two datetime values. leaves date intact
     * @param datetime $startdate Start datetime
     * @param datetime $endate End datetime
     */

    public function calculateRandomTime($startdate, $endate) {
        //Check if any image record exists where startime and endtime are within the page startime and endtime -- Can be done later
        $newstartdate = 0;
        $newenddate = 0;
        $startdate = explode(" ", '2010-05-11 14:18:54');
        $endate = explode(" ", '2010-05-11 14:21:08');

        $startime = explode(":", $startdate['1']);
        $endtime = explode(":", $endate['1']);
        //Check if its the same hour
        if ($startime[0] == $endtime[0]) {
            //Check if its the same minute -- Very easy
            if ($startime[1] == $endtime[1]) {
                do {
                    $sec1 = rand($startime[2], $endtime[2]);
                    $sec2 = rand($startime[2], $endtime[2]);
                } while ($sec1 == $sec2 || $sec2 < $sec1);
                $newstartdate = $startdate['0'] . " " . $startime[0] . ":" . $startime[1] . ":" . $sec1;
                $newenddate = $startdate['0'] . " " . $startime[0] . ":" . $startime[1] . ":" . $sec2;
                //Calculate a random seconds value thats greater than startime and less than endtime
            } else {
                //If endtime[1] is greater than startime[1]
                //Generate random value between the two
                do {
                    $min1 = rand($startime[1], $endtime[1]);
                    $min2 = rand($startime[1], $endtime[1]);
                } while ($min2 < $min1);
                if ($min1 == $min2) {
                    //Check if min1 is same as $startime[1]
                    if ($min1 == $startime[1]) {
                        //Generate a random value that is between $startime[2] and 60
                        $sec1 = $startime[2];
                        do {
                            $sec1 = rand($sec1, 60);
                            $sec2 = rand($sec1, 60);
                        } while ($sec2 < $sec1 || $sec2 == 60);

                        $newstartdate = $startdate['0'] . " " . $startime[0] . ":" . $min1 . ":" . $sec1;
                        $newenddate = $startdate['0'] . " " . $startime[0] . ":" . $min1 . ":" . $sec1;
                    } elseif ($min1 == $endtime[1]) {
                        //Get the start datetime
                        $sec1 = $endtime[2];
                        do {
                            $sec1 = rand($sec1, 60);
                            $sec2 = rand($sec1, 60);
                        } while ($sec2 < $sec1 || $sec2 == 60);
                        $newstartdate = $endate['0'] . " " . $endtime[0] . ":" . $min1 . ":" . $sec1;
                        $newenddate = $endate['0'] . " " . $endtime[0] . ":" . $min1 . ":" . $sec2;
                    }
                } else {
                    //If min1 == $startime find seconds btwn $startime[2] and 60
                    if ($min1 == $startime[1] && $min2 != $endtime[1]) {
                        do {
                            $sec1 = rand($startime[1], 60);
                        } while ($sec1 == $startime[1] || $sec1 == 60);
                        //Then get min2 seconds by getting a random btwn 0 and 60
                        do {
                            $sec2 = rand(0, 60);
                        } while ($sec2 == 60);
                        $newstartdate = $endate['0'] . " " . $startime[0] . ":" . $min1 . ":" . $sec1;
                        $newenddate = $endate['0'] . " " . $endtime[0] . ":" . $min2 . ":" . $sec2;
                    } elseif ($min1 != $startime[1] && $min2 != $endtime[1]) {
                        //Get min1 seconds by getting a random btwn 0 and 60
                        do {
                            $sec1 = rand(0, 60);
                        } while ($sec1 == 60);
                        //Then get min2 seconds by getting a random btwn $sec1 and 60
                        do {
                            $sec2 = rand(0, 60);
                        } while ($sec2 == 60);
                        $newstartdate = $endate['0'] . " " . $startime[0] . ":" . $min1 . ":" . $sec1;
                        $newenddate = $endate['0'] . " " . $endtime[0] . ":" . $min2 . ":" . $sec2;
                    } else {
                        //If min2 == $endtime find seconds btwn $endtime[2] and 60
                        do {
                            $sec2 = rand($endtime[1], 60);
                        } while ($sec2 == $endtime[1] || $sec2 == 60);
                        //Then get min1 seconds by getting a random btwn 0 and 60
                        do {
                            $sec1 = rand(0, 60);
                        } while ($sec1 == 60);
                        $newstartdate = $endate['0'] . " " . $startime[0] . ":" . $min1 . ":" . $sec1;
                        $newenddate = $endate['0'] . " " . $endtime[0] . ":" . $min2 . ":" . $sec2;
                    }
                }
            }
        }
        if ($newstartdate != 0 || $newenddate != 0)
            return array("newstartdate" => $newstartdate, "newenddate" => $newenddate);
        else
            return False;
    }

    /**
     * Method to delete a record
     * @param string $contextItemId Context Item Id
     */
    function deleteRecord($contextItemId) {
        // Delete the Record
        $this->delete('contextitemid', $contextItemId);
    }

}

?>
