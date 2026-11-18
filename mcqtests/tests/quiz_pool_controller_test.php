<?php
/** Exercise the real submission boundary with an immutable synthetic attempt paper. */
$GLOBALS['kewl_entry_point_run']=true;
class controller {
 public function getObject($name,$module=null){return $GLOBALS['objects'][$name];}
 public function getParam($name,$default=null){return $_POST[$name]??$default;}
 public function unsetSession($name){}
}
class dbTable extends controller {}
require dirname(__DIR__).'/classes/quizbankpool_class_inc.php';
require dirname(__DIR__).'/controller.php';
class fixturepool extends quizbankpool {
 public $saved;
 public function config($id){return ['draw_count'=>1];}
 public function paper($result,$user,$test){return $this->saved;}
 public function selectPrepared($sql,$params=[]){return $GLOBALS['marked']->rows;}
}
$pool=new fixturepool();$pool->saved=['totalmark'=>1,'questions'=>[['id'=>'chosen','testid'=>'test','questiontype'=>'mcq','mark'=>1,'answers'=>[['id'=>'right','testid'=>'test','questionid'=>'chosen','correct'=>1],['id'=>'wrong','testid'=>'test','questionid'=>'chosen','correct'=>0]]]]];
$GLOBALS['objects']['quizbankpool']=$pool;
$c=new mcqtests();(new ReflectionProperty($c,'userId'))->setValue($c,'owner');
$c->dbResults=new class {public $mark=null;public function getAttempt($id,$user,$test){return $id==='attempt'&&$user==='owner'&&$test==='test'?['mark'=>-1]:false;}public function addMark($id,$mark){$this->mark=$mark;}};
$c->dbMarked=$GLOBALS['marked']=new class {public $rows=[];public function addMarkedForAttempt($fields){$this->rows[]=$fields;}};
$save=new ReflectionMethod($c,'saveTest');$grade=new ReflectionMethod($c,'markTest');
$_POST=['id'=>'test','first'=>1,'count'=>3,'questionId1'=>'outside','ans1'=>'right','questionId2'=>'chosen','ans2'=>'foreign-answer','questionId3'=>'chosen','ans3'=>'right'];
$save->invoke($c,'attempt');if(count($c->dbMarked->rows)!==1||$c->dbMarked->rows[0]['questionid']!=='chosen')throw new RuntimeException('Forged selection accepted');
$c->dbMarked->rows[]=['questionid'=>'outside','answerid'=>'right'];$grade->invoke($c,'attempt');if($c->dbResults->mark!==1)throw new RuntimeException('Unselected answer affected grade');
$pool->saved=null;$c->dbMarked->rows=[];if($save->invoke($c,'attempt')!==false||$c->dbMarked->rows)throw new RuntimeException('Missing paper accepted');
if($save->invoke($c,'foreign-attempt')!==false)throw new RuntimeException('Foreign attempt accepted');
echo "PASS real controller: forged question/answer IDs ignored, saved selection graded, missing papers and foreign attempts rejected\n";
