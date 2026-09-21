<?php
$GLOBALS['kewl_entry_point_run']=true;
class controller {
 public $params=[];
 function getParam($key,$default=null){return $this->params[$key]??$default;}
 function nextAction($action,$params,$module){return [$action,$params,$module];}
}
require dirname(__DIR__).'/controller.php';
$c=new mcqgenerator();
function check($ok){if(!$ok)throw new RuntimeException('Unsafe legacy redirect');}
$_SERVER['REQUEST_METHOD']='GET';$c->params=['action'=>'examview','id'=>'saved','setid'=>'chapter'];$r=$c->dispatch();check($r[0]==='examview'&&$r[1]['id']==='saved'&&$r[1]['setid']==='chapter'&&$r[2]==='questiongenerator');
foreach(['generate','generatepart','more','delete','save','examadd','examsave','examdelete'] as $action){
 $_SERVER['REQUEST_METHOD']='POST';$c->params=['action'=>$action,'id'=>'saved'];$r=$c->dispatch();check(in_array($r[0],['view','examview'],true)&&$r[1]['reopen']==='1'&&$r[2]==='questiongenerator');
}
check($c->requiresLogin());echo "PASS: bookmarked IDs preserved; old writes and paid requests never replayed.\n";
