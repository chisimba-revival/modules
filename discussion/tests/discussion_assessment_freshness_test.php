<?php
/** Behavioural regression tests: evidence changes, stale AI and concurrent review. */
$GLOBALS['kewl_entry_point_run']=true;
class ChisimbaObject {
    public $services=array();
    public function getObject($name,$module=null){return $this->services[$name];}
}
class dbTable extends ChisimbaObject {
    public $rows=array(); public $queries=array(); public $stored=array();
    public function init($tableName=null,$pearDb=null,$errorCallback='globalPearErrorHandler'){}
    public function getArray($sql){$this->queries[]=$sql;return $this->rows;}
    public function getRow($key,$id){return $this->stored[$id]??false;}
    public function insert($row){$this->stored[$row['id']]=$row;return $row['id'];}
    public function getAll($sql){return array_values($this->stored);}
    public function update($key,$id,$row){$this->stored[$id]=array_merge($this->stored[$id],$row);return true;}
}
class controller extends ChisimbaObject {
    public $userId; public $objPost; public $objDiscussion;
    public $params=array();
    public function getParam($name,$default=null){return $this->params[$name]??$default;}
    public function nextAction($action,$params=array(),$module=null){return array('action'=>$action,'params'=>$params);}
}
require __DIR__.'/../classes/discussionassessmentstate_class_inc.php';
require __DIR__.'/../classes/dbdiscussionaimarkingjobs_class_inc.php';
require __DIR__.'/../classes/dbdiscussionassessmentmarks_class_inc.php';
require __DIR__.'/../controller.php';
$checks=0;
function check($ok,$label){global $checks;$checks++;if(!$ok){throw new RuntimeException($label);}}
$state=new discussionassessmentstate();
$first=array('postId'=>'p1','topicId'=>'t1','date'=>'2026-09-03 12:00:00','title'=>'Evidence','text'=>'First contribution','revision'=>'a');
$second=array_merge($first,array('postId'=>'p2','text'=>'Another contribution'));
$before=array($first);$after=array($first,$second);
$mark=array('mark'=>46,'evidence_fingerprint'=>$state->fingerprint($before));
check(!$state->needsReview($mark,$before),'Unchanged evidence stays reviewed');
check($state->needsReview($mark,$after),'Same-second reply requeues saved mark');
check($mark['mark']===46,'Review detection preserves the prior mark');
check($state->fingerprint($after)===$state->fingerprint(array_reverse($after)),'Query order does not change identity');
$edited=$before;$edited[0]['revision']='changed beyond the excerpt';
check($state->needsReview($mark,$edited),'Edits outside the excerpt invalidate review');
check($state->needsReview($mark,array()),'Removal of all evidence requeues existing mark');
check($state->needsReview(array('mark'=>46),$before),'Legacy mark requires explicit snapshot review');
check(!$state->needsReview(null,array()),'Empty learner excluded');
$many=array();for($i=0;$i<501;$i++){$many[]=array_merge($first,array('postId'=>'p'.$i));}
$manyMark=array('evidence_fingerprint'=>$state->fingerprint($many));$many[500]['text']='New evidence after the old limit';
check($state->needsReview($manyMark,$many),'Contribution 501 affects review identity');
$job=array('id'=>'job','contextcode'=>'course','requester_id'=>'teacher','status'=>'completed','evidence_json'=>json_encode($before),'result_json'=>'{}');
$jobs=new dbdiscussionaimarkingjobs();$jobs->services['discussionassessmentstate']=$state;$jobs->rows=array(array('id'=>'job'));$jobs->stored['job']=$job;
check((bool)$jobs->latest('discussion','student','course','teacher',false,$before),'Matching completed AI remains usable');
check($jobs->latest('discussion','student','course','teacher',false,$after)===false,'Old AI cannot block a new evidence snapshot');
check($jobs->latest('discussion','student','course','other',false,$before)===false,'Requester scope preserved');
$jobs->rows=array(array('status'=>'completed'));
check($jobs->latestStatus('discussion','student','course')===false,'Unverified completed status never disables retry');
$jobs->rows=array(array('status'=>'running'));
check($jobs->latestStatus('discussion','student','course')['status']==='running','Active job remains visible until it finishes');

// Exercise the real controller save path with in-memory persistence and a fixed clock.
$posts=new class {public $evidence;public function getAssessmentEvidence($d,$s){return $this->evidence;}};
$posts->evidence=$after;
$marks=new dbdiscussionassessmentmarks();
$marks->services['timeanddateservice']=new class {public function nowStorage(){return '2026-09-03 12:00:00';}};
$control=new discussion();
$control->services=array('discussionassessmentstate'=>$state,'dbdiscussionassessmentmarks'=>$marks,
    'usercontext'=>new class {public function getContextLecturers($c){return array();}public function getContextStudents($c){return array(array('id'=>'student'));}},
    'discussiondefaultrubric'=>new class {public function getStructuredRubric(){return array('criteria'=>array(array('objective'=>'Quality','maximumMark'=>100)));}});
foreach(array('contextCode'=>'course','userId'=>'teacher','objPost'=>$posts,'objDiscussion'=>new class {public function getDiscussion($id){return array('assessment_total_mark'=>50);}},'csrf'=>new class {public function consume($c,$t){return true;}}) as $name=>$value){$property=new ReflectionProperty(discussion::class,$name);$property->setValue($control,$value);}
$_SERVER['REQUEST_METHOD']='POST';
$control->params=array('id'=>'discussion','student_id'=>'student','criterion'=>array(92),'evidence_fingerprint'=>$state->fingerprint($before));
$save=new ReflectionMethod(discussion::class,'saveDiscussionMark');
$result=$save->invoke($control);
check($result['params']['message']==='evidencechanged'&&!$marks->stored,'Stale open form cannot write a mark');
$control->params['evidence_fingerprint']=$state->fingerprint($after);
$result=$save->invoke($control);$stored=array_values($marks->stored)[0];
check($result['params']['message']==='marksaved'&&$stored['mark']===46.0,'Fresh review saves scaled score');
check(!$state->needsReview($stored,$after),'Saved snapshot clears review queue');
$after[]=array_merge($second,array('postId'=>'p3'));
check($state->needsReview($stored,$after),'Reply after save requeues even with the same timestamp');
class MarkingViewFixture extends ChisimbaObject {
    public $html='';
    public function uri($params,$module){return 'index.php?'.http_build_query($params);}
    public function getParam($name,$default=''){return $default;}
    public function setVar($name,$value){if($name==='pageContent'){$this->html=$value;}}
    public function render($student){
        $discussionAssessment=array('id'=>'discussion','assessment_total_mark'=>50,'discussion_name'=>'Test');
        $discussionRubric=array('criteria'=>array(array('objective'=>'Quality','maximumMark'=>100)));
        $discussionStudents=array($student);$discussionAiAvailable=true;$discussionBatchCsrf='batch';
        include __DIR__.'/../templates/content/discussion_marking.php';
        return $this->html;
    }
}
$view=new MarkingViewFixture();$view->services['iconservice']=new class {public function render($name,$options){return '';}};
$learner=array('id'=>'student','profile'=>array('firstname'=>'Learner'),'evidence'=>$before,
    'evidence_fingerprint'=>$state->fingerprint($before),'needs_review'=>true,'mark'=>array('mark'=>46,'rubric_json'=>json_encode(array(array('score'=>92)))),
    'ai'=>false,'ai_status'=>false,'ai_csrf'=>'ai','save_csrf'=>'save');
$html=$view->render($learner);
check(str_contains($html,'data-reviewed="0"')&&str_contains($html,'data-ai-state="none"'),'Stale AI renders as eligible for fresh marking');
check(str_contains($html,'value="92"')&&str_contains($html,'prior mark 46 / 50'),'Prior scores remain visible while requeued');
check(str_contains($html,'name="evidence_fingerprint" value="'.$state->fingerprint($before).'"'),'Rendered save binds to displayed snapshot');
check(str_contains($html,'Review contributions (1)'),'Human reviewer can inspect evidence without AI');
$learner['ai']=array('id'=>'new-job','suggestion'=>array('criteria'=>array(array('score'=>95)),'feedback'=>'New suggestion'));
$html=$view->render($learner);
check(str_contains($html,'value="95"')&&str_contains($html,'prior mark 46 / 50'),'Fresh suggestion pre-fills draft while retaining prior mark');
check(str_contains($html,'data-ai-state="completed"'),'Current AI completion disables duplicate submission');
$learner['needs_review']=false;$html=$view->render($learner);
check(str_contains($html,'value="92"')&&str_contains($html,'data-reviewed="1"'),'Saved lecturer review overrides AI after review');
$learner['evidence'][0]['text']='<script>alert(1)</script>';$html=$view->render($learner);
check(str_contains($html,'&lt;script&gt;alert(1)&lt;/script&gt;'),'Evidence remains escaped in marking view');
echo "Discussion evidence freshness passed ($checks behavioural checks).\n";
