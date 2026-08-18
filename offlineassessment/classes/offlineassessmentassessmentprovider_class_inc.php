<?php
if (!$GLOBALS['kewl_entry_point_run']) { die('You cannot view this page directly'); }
/** Read-only Gradebook adapter. Offline Assessment remains owner of activities and marks. */
class offlineassessmentassessmentprovider extends ChisimbaObject
{
 private $assessments; private $marks;
 public function init(){ $this->assessments=$this->getObject('dbofflineassessments','offlineassessment'); $this->marks=$this->getObject('dbofflineassessmentmarks','offlineassessment'); }
 public function listActivities($contextCode){
  $out=array(); foreach($this->assessments->getForContext($contextCode) as $r){ if(($r['status']??'active')!=='active') continue; $out[]=array('id'=>$r['id'],'name'=>$r['name'],'classification'=>$r['classification'],'total_mark'=>(float)$r['maximum_mark'],'closing_date'=>null); } return $out;
 }
 public function getActivity($contextCode,$activityId){ foreach($this->listActivities($contextCode) as $a){ if((string)$a['id']===(string)$activityId)return $a; } return false; }
 public function getStudentResult($contextCode,$activityId,$userId,$rule='latest_completed'){
  $a=$this->getActivity($contextCode,$activityId); if(!$a || $a['total_mark']<=0)return array('status'=>'not_attempted','mark_percent'=>null);
  $m=$this->marks->findMark($activityId,$userId); if(!$m)return array('status'=>'not_attempted','mark_percent'=>null);
  $pct=((float)$m['mark']/$a['total_mark'])*100; return array('status'=>'marked','mark_percent'=>max(0.0,min(100.0,$pct)));
 }
}
?>