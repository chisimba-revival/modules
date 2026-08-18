<?php
if (!$GLOBALS['kewl_entry_point_run']) { die('You cannot view this page directly'); }
class dbofflineassessmentmarks extends dbtable
{
 private $history;
 public function init($tableName=null,$pearDb=null,$errorCallback='globalPearErrorHandler'){ parent::init('tbl_offlineassessment_marks'); $this->history=$this->getObject('dbofflineassessmentmarkhistory','offlineassessment'); }
 public function findMark($assessmentId,$studentId){ $r=$this->getAll("WHERE assessment_id='".addslashes($assessmentId)."' AND student_id='".addslashes($studentId)."' LIMIT 1"); return is_array($r)&&$r?$r[0]:false; }
 public function getForAssessment($assessmentId){ $r=$this->getAll("WHERE assessment_id='".addslashes($assessmentId)."'"); $out=array(); foreach((array)$r as $row){$out[$row['student_id']]=$row;} return $out; }
 public function saveAuditedMark($assessmentId,$studentId,$newMark,$reason,$userId){
  $old=$this->findMark($assessmentId,$studentId); $now=date('Y-m-d H:i:s');
  if($old && (float)$old['mark']===(float)$newMark) return true;
  if($old && trim($reason)==='') return false;
  if($old){ $ok=$this->update('id',$old['id'],array('mark'=>$newMark,'entered_by'=>$userId,'date_updated'=>$now)); $event='changed'; $oldMark=$old['mark']; }
  else { $ok=$this->insert(array('assessment_id'=>$assessmentId,'student_id'=>$studentId,'mark'=>$newMark,'entered_by'=>$userId,'date_created'=>$now,'date_updated'=>$now)); $event='initial'; $oldMark=null; }
  if(!$ok) return false;
  return $this->history->append($assessmentId,$studentId,$oldMark,$newMark,$event,$reason,$userId,$now);
 }
}
?>