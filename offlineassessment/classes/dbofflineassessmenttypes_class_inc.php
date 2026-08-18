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
 public function moveType($id,$direction){
  $types=$this->getAllTypes(); $index=null;
  foreach($types as $i=>$type){ if($type['id']===$id){$index=$i;break;} }
  if($index===null)return false;
  $swap=$direction==='up'?$index-1:$index+1;
  if($swap<0||$swap>=count($types))return false;
  $a=$types[$index]; $b=$types[$swap];
  $aOrder=(int)$a['sort_order']; $bOrder=(int)$b['sort_order'];
  if($aOrder===$bOrder){$aOrder=$index+1;$bOrder=$swap+1;}
  $now=date('Y-m-d H:i:s');
  $ok1=$this->update('id',$a['id'],array('sort_order'=>$bOrder,'date_updated'=>$now));
  $ok2=$this->update('id',$b['id'],array('sort_order'=>$aOrder,'date_updated'=>$now));
  return $ok1&&$ok2;
 }
 public function saveType($id,$name,$sortOrder,$status,$userId){
  $now=date('Y-m-d H:i:s');
  if($id){ return $this->update('id',$id,array('name'=>$name,'sort_order'=>(int)$sortOrder,'status'=>$status,'date_updated'=>$now)); }
  return $this->insert(array('name'=>$name,'sort_order'=>(int)$sortOrder,'status'=>$status,'is_seed'=>'N','created_by'=>$userId,'date_created'=>$now,'date_updated'=>$now));
 }
}
?>