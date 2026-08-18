<?php
if (!$GLOBALS['kewl_entry_point_run']) { die('You cannot view this page directly'); }
class dbofflineassessments extends dbtable
{
 public function init($tableName=null,$pearDb=null,$errorCallback='globalPearErrorHandler'){ parent::init('tbl_offlineassessment_assessments'); }
 public function getForContext($context){ $r=$this->getAll("WHERE context_code='".addslashes($context)."' ORDER BY assessment_date DESC, date_created DESC"); return is_array($r)?$r:array(); }
 public function findInContext($id,$context){ $r=$this->getAll("WHERE id='".addslashes($id)."' AND context_code='".addslashes($context)."' LIMIT 1"); return is_array($r)&&$r?$r[0]:false; }
 public function saveAssessment($id,array $data,$userId){
  $data['date_updated']=date('Y-m-d H:i:s');
  if($id) return $this->update('id',$id,$data);
  $data['created_by']=$userId; $data['date_created']=$data['date_updated']; return $this->insert($data);
 }
}
?>