<?php
if(empty($GLOBALS['kewl_entry_point_run']))die('You cannot view this page directly');
class sitemetrics extends dbTable
{
 public function init($tableName=null,$pearDb=null,$errorCallback='globalPearErrorHandler'){parent::init('tbl_users',$pearDb,$errorCallback);}
 public function countUsers(){return $this->countFrom('tbl_users');}
 public function countCourses(){return $this->countFrom('tbl_context');}
 private function countFrom($table){$rows=$this->getArray('SELECT COUNT(*) AS total FROM '.$table);return isset($rows[0]['total'])?(int)$rows[0]['total']:0;}
}
?>
