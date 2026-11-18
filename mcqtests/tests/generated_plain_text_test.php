<?php
/** AI output is plain text, including legacy UTF-8 storage. @author Derek Keats */
$GLOBALS['kewl_entry_point_run']=true;
class ChisimbaObject {public function getObject($name,$module=null){return $GLOBALS['objects'][$name];}}
require dirname(__DIR__).'/classes/bankquestion_class_inc.php';require dirname(__DIR__).'/classes/mcqaigenerator_class_inc.php';
$GLOBALS['objects']['bankquestion']=new bankquestion();
$q=new class {public $row,$actor;function getMaxOrder($id){return 0;}function addQuestion($row,$id=null,$save=null,$actor=null){$this->row=$row;$this->actor=$actor;return 'question';}function getTotalMarks($id){return 1;}};
$a=new class {public $rows=[];function addAnswers($row){$this->rows[]=$row;return 'answer';}};
$GLOBALS['objects']['dbquestions']=$q;$GLOBALS['objects']['dbanswers']=$a;
$GLOBALS['objects']['dbtestadmin']=new class {function getRow($key,$id){return ['id'=>$id];}function setTotal($id,$total){}};
$GLOBALS['objects']['bankstore']=new class {function saveMetadata($id,$content){}};
$service=new mcqaigenerator();$service->init();$result=$service->insertQuestions('test',[['stem'=>'<script>alert(1)</script> 🌱','options'=>['<img src=x onerror=alert(1)>','B','C','D'],'correctIndex'=>0,'sourceBasis'=>'Reference']], 'author');
if(!$result['ok']||str_contains($q->row['question'],'<script>')||!str_contains($q->row['question'],'&lt;script&gt;')||str_contains($a->rows[0]['answer'],'<img')||str_contains($q->row['question'],'🌱')||$q->actor!=='author')throw new RuntimeException('Generated text or actor boundary failed');
echo "PASS generated question and answer markup stays inert, supplementary Unicode survives, and worker authorship is explicit\n";
