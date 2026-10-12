<?php
/** Verify ordering is a protected mutation and conflicts reach the client. */
if(PHP_SAPI!=='cli')exit(64);
if($argc===1){
 foreach(['success'=>200,'csrf'=>403,'permission'=>403,'status'=>403,'malformed'=>422,'conflict'=>409,'storage'=>500] as $case=>$status){
  $result=json_decode(shell_exec(escapeshellarg(PHP_BINARY).' '.escapeshellarg(__FILE__).' '.$case),true);
  if(!$result||$result['status']!==$status||$result['ok']!==($case==='success'))throw new RuntimeException('Failed ordering controller case '.$case);
  echo 'PASS ordering '.$case."\n";
 }exit;
}
$GLOBALS['kewl_entry_point_run']=true;$_SERVER['REQUEST_METHOD']='POST';$case=$argv[1];
class controller{
 public function getParam($name,$default=''){
  return ['taskid'=>str_repeat('a',32),'status'=>$GLOBALS['case']==='status'?'bad':'not_started','beforetaskid'=>str_repeat('b',32),'expectedstatus'=>'not_started','order'=>$GLOBALS['case']==='malformed'?'oops':json_encode([str_repeat('b',32)]),'response'=>'json','csrf_token'=>'fixture'][$name]??$default;
 }
 public function getObject($name,$module=null){return new class{public function languageText($key,$module){return 'Reload the board before trying again.';}};}
}
require dirname(__DIR__).'/controller.php';$c=new kanban();
$c->csrf=new class{public function consume($scope,$token){return $GLOBALS['case']!=='csrf';}public function issueForSession($scope){return 'fresh';}};
$c->service=new class{public function board($id,$permission){if($permission!=='edit')throw new RuntimeException('Wrong permission');return $GLOBALS['case']==='permission'?false:['id'=>$id];}};
$c->tasks=new class{
 public function one($id){return ['id'=>$id,'boardid'=>str_repeat('c',32)];}
 public function moveTask($id,$status,$before,$expectedStatus,$expectedOrder){
  if(in_array($GLOBALS['case'],['csrf','permission','status','malformed'],true))throw new RuntimeException('Unauthorised mutation');
  if($GLOBALS['case']==='conflict')throw new DomainException('Changed');
  if($GLOBALS['case']==='storage')throw new RuntimeException('Storage failed');
  if($before!==str_repeat('b',32)||$expectedOrder!==[$before]||$expectedStatus!=='not_started')throw new RuntimeException('Lost positioning contract');
  return true;
 }
};
ob_start(function($output){$r=json_decode($output,true);$r['status']=http_response_code();return json_encode($r);});$c->dispatch('movetask');
