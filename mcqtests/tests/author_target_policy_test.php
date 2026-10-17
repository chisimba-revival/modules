<?php
/** Exercise the actual authoring boundary with cross-course identifiers. */
$GLOBALS['kewl_entry_point_run']=true;
class controller {
    public array $params=array();
    public function getParam($key,$default=null){return $this->params[$key]??$default;}
}
require dirname(__DIR__).'/controller.php';
class FixtureRows {
    public function __construct(public array $rows){}
    public function getRow($key,$id){return $this->rows[$id]??false;}
    public function getQuestion($id){return isset($this->rows[$id])?array($this->rows[$id]):array();}
}
$c=new mcqtests();$c->contextCode='ours';
$c->dbTestadmin=new FixtureRows(array('test'=>array('context'=>'ours','status'=>'inactive'),'foreign'=>array('context'=>'other','status'=>'inactive'),'open'=>array('context'=>'ours','status'=>'open')));
$c->dbQuestions=new FixtureRows(array('q'=>array('testid'=>'test','categoryid'=>'cat'),'foreignq'=>array('testid'=>'foreign','categoryid'=>'foreigncat')));
$c->dbAnswers=new FixtureRows(array('a'=>array('questionid'=>'q'),'foreigna'=>array('questionid'=>'foreignq')));
$c->dbCategory=new FixtureRows(array('cat'=>array('contextcode'=>'ours'),'foreigncat'=>array('contextcode'=>'other')));
$c->dbDescription=new FixtureRows(array('desc'=>array('categoryid'=>'cat'),'foreigndesc'=>array('categoryid'=>'foreigncat')));
$method=new ReflectionMethod($c,'authorTargetAllowed');
function verify($action,$params,$allowed){global $c,$method;$c->params=$params;if($method->invoke($c,$action)!==$allowed)throw new RuntimeException($action.' policy mismatch');echo 'PASS '.$action.' '.($allowed?'allowed':'denied')."\n";}
verify('applyaddanswer',array('testId'=>'test','questionId'=>'q'),true);
verify('applyaddanswer',array('testId'=>'foreign','questionId'=>'foreignq'),false);
verify('applyaddanswer',array('testId'=>'test','questionId'=>'foreignq'),false);
verify('applyaddanswer',array('testId'=>'test','questionId'=>'q','answerId'=>'foreigna'),false);
verify('deletecat',array('id'=>'foreigncat'),false);
verify('deletecat',array('id'=>'cat'),true);
verify('deletedesc',array('id'=>'foreigndesc'),false);
verify('deletedesc',array('id'=>'desc'),true);
verify('deletescq',array('test'=>'test','id'=>'foreignq'),false);
verify('deletescq',array('test'=>'test','id'=>'q'),true);
verify('delete',array('id'=>'open'),false);
