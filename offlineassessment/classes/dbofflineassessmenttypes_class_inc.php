<?php
if (!$GLOBALS['kewl_entry_point_run']) { die('You cannot view this page directly'); }
class dbofflineassessmenttypes extends dbtable
{
 public function init($tableName=null,$pearDb=null,$errorCallback='globalPearErrorHandler'){ parent::init('tbl_offlineassessment_types'); }
 public function getActive(){ $r=$this->getAll("WHERE status='active' ORDER BY sort_order, name"); return is_array($r)?$r:array(); }
 public function getAllTypes(){ $r=$this->getAll('ORDER BY sort_order, name'); return is_array($r)?$r:array(); }
 public function ensureSeeds($userId){
  if (count($this->getAllTypes())>0) return;
  $names=array('Exam','Class test','Field assignment','Practical assessment','Oral assessment'); $i=0; $now=date('Y-m-d H:i:s');
  foreach($names as $name){ $this->insert(array('name'=>$name,'sort_order'=>++$i,'status'=>'active','is_seed'=>'Y','created_by'=>$userId,'date_created'=>$now,'date_updated'=>$now)); }
 }
 public function saveType($id,$name,$sortOrder,$status,$userId){
  $now=date('Y-m-d H:i:s');
  if($id){ return $this->update('id',$id,array('name'=>$name,'sort_order'=>(int)$sortOrder,'status'=>$status,'date_updated'=>$now)); }
  return $this->insert(array('name'=>$name,'sort_order'=>(int)$sortOrder,'status'=>$status,'is_seed'=>'N','created_by'=>$userId,'date_created'=>$now,'date_updated'=>$now));
 }
}
?>