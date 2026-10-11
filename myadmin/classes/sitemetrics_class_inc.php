<?php
if(empty($GLOBALS['kewl_entry_point_run']))die('You cannot view this page directly');
class sitemetrics extends dbTable
{
 public function init($tableName=null,$pearDb=null,$errorCallback='globalPearErrorHandler'){parent::init('tbl_users',$pearDb,$errorCallback);}
 public function countUsers(){return $this->countFrom('tbl_users');}
 public function countCourses(){return $this->countFrom('tbl_context');}
 public function newRegistrations(){
  $today=date('Y-m-d');$week=date('Y-m-d',strtotime('-6 days'));$month=date('Y-m-01');
  $counts=$this->selectPrepared('SELECT SUM(creationdate=?) AS today_count,SUM(creationdate>=?) AS week_count,SUM(creationdate>=?) AS month_count FROM tbl_users',[$today,$week,$month]);
  $recent=$this->getArray('SELECT userid,firstname,surname,creationdate FROM tbl_users ORDER BY creationdate DESC,id DESC LIMIT 10');$row=is_array($counts)&&isset($counts[0])?$counts[0]:array();
  return array('today'=>(int)($row['today_count']??0),'week'=>(int)($row['week_count']??0),'month'=>(int)($row['month_count']??0),'recent'=>is_array($recent)?$recent:array());
 }
 private function countFrom($table){$rows=$this->getArray('SELECT COUNT(*) AS total FROM '.$table);return isset($rows[0]['total'])?(int)$rows[0]['total']:0;}
}
?>
