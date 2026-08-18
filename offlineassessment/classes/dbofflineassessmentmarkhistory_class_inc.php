<?php
if (!$GLOBALS['kewl_entry_point_run']) { die('You cannot view this page directly'); }
class dbofflineassessmentmarkhistory extends dbtable
{
 public function init($tableName=null,$pearDb=null,$errorCallback='globalPearErrorHandler'){ parent::init('tbl_offlineassessment_mark_history'); }
 public function append($assessmentId,$studentId,$oldMark,$newMark,$event,$reason,$userId,$when){ return $this->insert(array('assessment_id'=>$assessmentId,'student_id'=>$studentId,'old_mark'=>$oldMark,'new_mark'=>$newMark,'event_type'=>$event,'reason'=>$reason,'changed_by'=>$userId,'date_changed'=>$when)); }
 public function getForAssessment($assessmentId){ $r=$this->getAll("WHERE assessment_id='".addslashes($assessmentId)."' ORDER BY date_changed DESC"); return is_array($r)?$r:array(); }
}
?>