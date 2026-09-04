<?php
if(empty($GLOBALS['kewl_entry_point_run']))die('You cannot view this page directly');
class teachinginsights extends ChisimbaObject
{
 private $user;private $userContext;private $contexts;private $modules;private $language;
 public function init(){
  $this->user=$this->getObject('user','security');
  $this->userContext=$this->getObject('usercontext','context');
  $this->contexts=$this->getObject('dbcontext','context');
  $this->modules=$this->getObject('modules','modulecatalogue');
  $this->language=$this->getObject('language','language');
 }
 public function courseInsights($userId){
  $rows=array();
  foreach(array_values(array_unique((array)$this->userContext->getContextWhereLecturer($userId))) as $code){
   $details=$this->contexts->getContextDetails($code);
   if(!is_array($details)||empty($details['title']))continue;
   $authors=array();foreach((array)$this->userContext->getContextLecturers($code) as $author){$id=$this->memberId($author);if($id!=='')$authors[$id]=true;}
   $students=array_values(array_filter((array)$this->userContext->getContextStudents($code),function($student)use($authors){$id=$this->memberId($student);return $id!==''&&!isset($authors[$id]);}));
   $rows[]=array('code'=>(string)$code,'title'=>(string)$details['title'],'students'=>count($students),'progress'=>$this->averageProgress($code,$students),'outstanding'=>$this->outstanding($code,$students));
  }
  usort($rows,fn($a,$b)=>strcasecmp($a['title'],$b['title']));
  return $rows;
 }
 private function memberId($member){return (string)($member['id']??$member['userid']??$member['userId']??'');}
 private function averageProgress($code,array $students){
  if(!$students||!$this->modules->checkIfRegistered('contextcontent'))return null;
  try{$journey=$this->getObject('learningjourney','contextcontent');$sum=0;$count=0;foreach($students as $student){$id=$this->memberId($student);if($id==='')continue;$state=$journey->getState($code,$id);$total=max(0,(int)($state['total']??0));if($total===0)continue;$sum+=min(100,round((max(0,(int)($state['visited']??0))/$total)*100));$count++;}return $count?(int)round($sum/$count):null;}catch(Throwable $failure){return null;}
 }
 private function outstanding($code,array $students){
  if(!$students||!$this->modules->checkIfRegistered('gradebook'))return array();
  $queue=array();
   try{$registry=$this->getObject('assessmentproviderregistry','gradebook');foreach((array)$registry->all() as $provider){if(!in_array('manual_marking',(array)($provider['capabilities']??array()),true))continue;$adapter=$registry->adapter($provider['key']);if(!$adapter||!method_exists($adapter,'listActivities')||!method_exists($adapter,'getStudentResult'))continue;foreach((array)$adapter->listActivities($code) as $activity){$studentIds=array_values(array_filter(array_map(fn($student)=>$this->memberId($student),$students)));if(method_exists($adapter,'getOutstandingReviewCount')){$count=(int)$adapter->getOutstandingReviewCount($code,$activity['id'],$studentIds);}else{$count=0;foreach($studentIds as $id){$result=$adapter->getStudentResult($code,$activity['id'],$id);if(($result['status']??'')==='submitted')$count++;}}$target=$count&&method_exists($adapter,'getLaunchTarget')?$adapter->getLaunchTarget($code,$activity['id'],'author'):false;$url=is_array($target)&&!empty($target['module'])?$this->uri((array)($target['params']??array()),$target['module']):'';if($count)$queue[]=array('provider'=>$provider['key'],'activity'=>(string)$activity['id'],'name'=>(string)$activity['name'],'count'=>$count,'url'=>$url);}}}catch(Throwable $failure){return array();}
  usort($queue,fn($a,$b)=>strcasecmp($a['name'],$b['name']));return $queue;
 }
 private function text($key,$fallback,$tokens=null){return $tokens===null?$this->language->languageText('mod_myteaching_'.$key,'myteaching',$fallback):$this->language->code2Txt('mod_myteaching_'.$key,'myteaching',$tokens,$fallback);}
 public function renderCard(array $course){
  $e=fn($v)=>htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');
  $progress=$course['progress']===null?'Not available':$course['progress'].'%';$html='<div class="teaching-insight-course"><dl><div><dt>'.$e($this->text('students','[-readonlys-]',array())).'</dt><dd>'.$course['students'].'</dd></div><div><dt>'.$e($this->text('average_progress','Average progress')).'</dt><dd>'.$e($progress).'</dd></div><div><dt>'.$e($this->text('outstanding','Assessments outstanding')).'</dt><dd>'.array_sum(array_column($course['outstanding'],'count')).'</dd></div></dl><div class="teaching-insight-course__queue"><label for="assessment-'.$e($course['code']).'">'.$e($this->text('select_assessment','Select work awaiting marking')).'</label><select id="assessment-'.$e($course['code']).'" data-course="'.$e($course['code']).'" onchange="this.nextElementSibling.href=this.options[this.selectedIndex].dataset.url">';
   if(!$course['outstanding'])$html.='<option value="">'.$e($this->text('no_outstanding','Nothing awaiting marking')).'</option>';else{foreach($course['outstanding'] as $item){$reviewWord=$item['count']===1?'review':'reviews';$html.='<option value="'.$e($item['provider'].'|'.$item['activity']).'" data-url="'.$e($item['url']).'">'.$e($item['name'].' — '.$item['count'].' learner '.$reviewWord.' awaiting action').'</option>';}}
  if(!$course['outstanding'])return $html.'</select><button type="button" class="button" disabled>'.$e($this->text('action_later','Open action')).'</button></div></div>';
  return $html.'</select><a class="button" href="'.$e($course['outstanding'][0]['url']).'">'.$e($this->text('action_later','Open action')).'</a></div></div>';
 }
}
?>
