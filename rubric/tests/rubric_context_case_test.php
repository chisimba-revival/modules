<?php
/** Course ownership survives MDB2 lower-case portability without weakening scope. */
$GLOBALS['kewl_entry_point_run']=true;
class ChisimbaObject { public function getObject($name,$module=null){return $GLOBALS['fixtures'][$name];} }
require dirname(__DIR__).'/classes/rubricservice_class_inc.php';
$tables=new class { public $lower=true; public function listSingle($id){return [['id'=>$id,($this->lower?'contextcode':'contextCode')=>'course_a','title'=>'Evidence','description'=>'','rows'=>0,'cols'=>0]];}public function listAll($context,$owner){return $this->listSingle('test');} };
$GLOBALS['fixtures']=['dbrubrictables'=>$tables,'dbrubricobjectives'=>new stdClass(),'dbrubricperformances'=>new stdClass(),'dbrubriccells'=>new stdClass()];
$r=new rubricservice();$r->init();
foreach([true,false] as $lower){$tables->lower=$lower;if($r->getRubric('test')['contextCode']!=='course_a'||$r->listRubrics('course_b')[0]['contextCode']!=='course_a')throw new RuntimeException('Stored course ownership lost or replaced');}
echo "PASS rubric ownership with lower-case and mixed-case database columns.\n";
