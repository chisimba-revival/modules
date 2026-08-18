<?php
if (!$GLOBALS['kewl_entry_point_run']) { die('You cannot view this page directly'); }
class offlineassessment extends controller
{
 public $objLanguage;
 private $user; private $perm; private $context; private $contextCode; private $assessments; private $types; private $marks; private $history; private $userContext;
 public function init(){
  $this->user=$this->getObject('user','security'); $this->objLanguage=$this->getObject('language','language'); $this->perm=$this->getObject('contextcondition','contextpermissions');
  $this->context=$this->getObject('dbcontext','context'); $this->contextCode=$this->context->getContextCode(); $this->userContext=$this->getObject('usercontext','context');
  $this->assessments=$this->getObject('dbofflineassessments','offlineassessment'); $this->types=$this->getObject('dbofflineassessmenttypes','offlineassessment'); $this->marks=$this->getObject('dbofflineassessmentmarks','offlineassessment'); $this->history=$this->getObject('dbofflineassessmentmarkhistory','offlineassessment');
 }
 private function mayManage(){ return $this->user->isAdmin() || $this->perm->isContextMember('Lecturers'); }
 private function assessment(){ $id=trim((string)$this->getParam('id','')); return $this->assessments->findInContext($id,$this->contextCode); }
 public function dispatch($action){
  if(!$this->mayManage()) return $this->nextAction(null,array('error'=>'noaccess'),'_default');
  $this->types->ensureSeeds($this->user->userId()); $action=$this->getParam('action',null);
  switch($action){
   case 'edit': $a=$this->assessment(); $this->setVar('assessment',$a?:array()); $this->setVar('types',$this->types->getActive()); return 'edit_tpl.php';
   case 'save': return $this->saveAssessment();
   case 'types': $this->setVar('types',$this->types->getAllTypes()); return 'types_tpl.php';
   case 'savetype': return $this->saveType();
   case 'movetype': return $this->moveType();
   case 'marks': return $this->marksPage();
   case 'savemarks': return $this->saveMarks();
   case 'audit': $a=$this->assessment(); if(!$a)return $this->nextAction(null,array('error'=>'invalid')); $this->setVar('assessment',$a); $this->setVar('history',$this->history->getForAssessment($a['id'])); return 'audit_tpl.php';
   default: $this->setVar('assessments',$this->assessments->getForContext($this->contextCode)); $this->setVar('types',$this->types->getAllTypes()); return 'main_tpl.php';
  }
 }
 private function saveAssessment(){
  $id=trim((string)$this->getParam('id','')); if($id && !$this->assessments->findInContext($id,$this->contextCode)) return $this->nextAction(null,array('error'=>'invalid'));
  $name=trim((string)$this->getParam('name','')); $type=trim((string)$this->getParam('type_id','')); $class=$this->getParam('classification','summative'); $max=(float)$this->getParam('maximum_mark',100);
  if($name===''||$type===''||$max<=0) return $this->nextAction('edit',array('id'=>$id,'error'=>'invalid'));
  $date=trim((string)$this->getParam('assessment_date','')); if($date!=='')$date.=' 00:00:00';
  $data=array('context_code'=>$this->contextCode,'type_id'=>$type,'name'=>$name,'classification'=>$class==='formative'?'formative':'summative','maximum_mark'=>$max,'assessment_date'=>$date===''?null:$date,'description'=>trim((string)$this->getParam('description','')),'status'=>$this->getParam('status','active')==='inactive'?'inactive':'active');
  $this->assessments->saveAssessment($id,$data,$this->user->userId()); return $this->nextAction(null,array('saved'=>'1'));
 }
 private function moveType(){
  $id=trim((string)$this->getParam('id','')); $direction=$this->getParam('direction','');
  if($id!=='' && ($direction==='up'||$direction==='down')) $this->types->moveType($id,$direction);
  return $this->nextAction('types');
 }
 private function saveType(){ $name=trim((string)$this->getParam('name','')); if($name!=='')$this->types->saveType(trim((string)$this->getParam('id','')),$name,(int)$this->getParam('sort_order',0),$this->getParam('status','active')==='inactive'?'inactive':'active',$this->user->userId()); return $this->nextAction('types'); }
 private function students(){
  $out=array(); foreach((array)$this->userContext->getContextStudents($this->contextCode) as $s){ $id=$s['id']; $out[]=array('id'=>$id,'firstname'=>$this->user->getItemFromPkId($id,'firstname'),'surname'=>$this->user->getItemFromPkId($id,'surname'),'username'=>$this->user->getItemFromPkId($id,'username')); } return $out;
 }
 private function marksPage(){ $a=$this->assessment(); if(!$a)return $this->nextAction(null,array('error'=>'invalid')); $this->setVar('assessment',$a); $this->setVar('students',$this->students()); $this->setVar('marks',$this->marks->getForAssessment($a['id'])); return 'marks_tpl.php'; }
 private function saveMarks(){
  $a=$this->assessment(); if(!$a)return $this->nextAction(null,array('error'=>'invalid')); $errors=array();
  foreach($this->students() as $s){ $raw=trim((string)$this->getParam('mark_'.$s['id'],'')); if($raw==='')continue; if(!is_numeric($raw)||(float)$raw<0||(float)$raw>(float)$a['maximum_mark']){$errors[$s['id']]='range';continue;} $old=$this->marks->findMark($a['id'],$s['id']); $reason=trim((string)$this->getParam('reason_'.$s['id'],'')); if($old&&(float)$old['mark']!==(float)$raw&&$reason===''){$errors[$s['id']]='reason';continue;} $this->marks->saveAuditedMark($a['id'],$s['id'],(float)$raw,$reason,$this->user->userId()); }
  if($errors){$this->setVar('markErrors',$errors); return $this->marksPage();} return $this->nextAction('marks',array('id'=>$a['id'],'saved'=>'1'));
 }
}
?>