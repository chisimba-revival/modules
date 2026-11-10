<?php
/** Local disposable fixture only; exercise canonical MCQ persistence and lookup. @author Derek Keats */
if(PHP_SAPI!=='cli'||getenv('QUESTIONWORKSHOP_LOCAL_TEST')!=='1')exit(64);
chdir('/var/www/html/ch');$GLOBALS['kewl_entry_point_run']=true;$_SERVER['REQUEST_METHOD']='CLI';$_SERVER['HTTP_HOST']='localhost';$_SERVER['SCRIPT_NAME']='/index.php';$_SERVER['QUERY_STRING']='';require 'classes/core/engine_class_inc.php';$engine=new engine();
$f=json_decode(file_get_contents('/tmp/workshop-fixture.json'),true);
if(!preg_match('/^qwqa[a-f0-9]{8}$/D',$f['tag']??''))throw new RuntimeException('Disposable fixture required');
require_once 'packages/mcqgenerator/classes/workshopservice_class_inc.php';
class WorkshopImportFixture extends workshopservice {
 public $fixture,$runtime,$permitted=true;
 public function getObject($name,$module=null){
  if($name==='user')return new class($this) {private $parent;public function __construct($p){$this->parent=$p;}public function userId(){return $this->parent->fixture['ids']['teacher'];}public function isAdmin(){return false;}public function isCourseAdmin($c){return false;}public function isContextLecturer($id,$c){return $this->parent->permitted&&$c===$this->parent->fixture['tag'];}};
  if($name==='dbcontext')return new class($this->fixture['tag']){private $tag;public function __construct($t){$this->tag=$t;}public function getContextDetails($c){return $c===$this->tag?['title'=>'Disposable course']:false;}};
  if($name==='workshoppolicy')return new class($this->fixture){private $f;public function __construct($f){$this->f=$f;}public function owner($row){return $row['ownerid']===$this->f['ids']['teacher'];}};
  return $this->runtime->getObject($name,$module?:'mcqgenerator');
 }
}
$s=new WorkshopImportFixture();$s->fixture=$f;$s->runtime=$engine;$s->objEngine=$engine;
$s->permitted=false;try{$s->importCourse($f['set'],$f['tag']);throw new RuntimeException('Permission bypass');}catch(DomainException $e){if($e->getMessage()!=='course_forbidden')throw $e;}
$s->permitted=true;$id=$s->importCourse($f['set'],$f['tag']);
if($id!==$s->importCourse($f['set'],$f['tag']))throw new RuntimeException('Duplicate import');
$tests=$engine->getObject('dbtestadmin','mcqtests');$row=$tests->getRow('id',$id);
if($row['context']!==$f['tag']||$row['status']!=='inactive')throw new RuntimeException('Unsafe target');
$questions=$engine->getObject('dbquestions','mcqtests')->getQuestions($id);
if(count($questions)!==3)throw new RuntimeException('Wrong count');
$bank=json_decode($tests->getContextQuestions($f['tag'],'different-test','mcq','',0,20),true);
if(($bank['totalcount']??0)!==3)throw new RuntimeException('Course question lookup cannot see imported pool');

echo "PASS canonical MCQ import, inactive status, teaching permission denial, idempotency and course question-pool lookup.\n";
