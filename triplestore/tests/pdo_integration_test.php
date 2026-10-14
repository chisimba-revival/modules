<?php
/** Run from the local application root with CHISIMBA_TRIPLESTORE_TEST=1.
 * Requires normal catalogue installation. Every test write is rolled back.
 */
if (PHP_SAPI!=='cli' || getenv('CHISIMBA_TRIPLESTORE_TEST')!=='1') exit("Set CHISIMBA_TRIPLESTORE_TEST=1 on a disposable/local installation.\n");
$GLOBALS['kewl_entry_point_run']=true;
$_SERVER['REQUEST_METHOD']='CLI';$_SERVER['HTTP_HOST']='chisimba.test:8445';$_SERVER['SCRIPT_NAME']='/ch/index.php';$_SERVER['QUERY_STRING']='';
require 'classes/core/engine_class_inc.php';$engine=new engine();
set_error_handler(function($severity,$message,$file,$line){if(error_reporting()&$severity)throw new ErrorException($message,0,$severity,$file,$line);return false;});
$store=$engine->getObject('dbtriplestore','triplestore');
require_once 'packages/mentors/classes/mentorfuncs_class_inc.php';
$mentors=(new ReflectionClass('mentorfuncs'))->newInstanceWithoutConstructor();
(new ReflectionProperty($mentors,'objDbTripleStore'))->setValue($mentors,$store);
function verify($ok,$label){if(!$ok)throw new RuntimeException($label);}
$subject='qa-'.bin2hex(random_bytes(6));$before=$store->browse([])['total'];
try {
 $store->transaction(function()use($store,$mentors,$subject){
  $id=$store->insert($subject,'isMentorOf',"learner' OR '1'='1");
  verify(is_string($id),'insert');
  verify($store->getTriples(['object'=>"learner' OR '1'='1"])[0]['id']===$id,'bound quoted value');
  verify($store->getTriples(['subject'=>"' OR 1=1 --"])===[],'injection value');
  verify($mentors->getMentors("learner' OR '1'='1")===[$subject],'Mentors lookup');
  verify($mentors->getStudents($subject)===["learner' OR '1'='1"],'students lookup');
  $mentors->addMentor($subject,'qa-learner');$mentors->addMentor($subject,'qa-learner');
  verify(count($store->getTriples(['subject'=>$subject,'object'=>'qa-learner']))===1,'duplicate mentor relationship');
  $mentors->addStudent($subject,'qa-learner');$mentors->addStudent($subject,'qa-learner');
  verify(count($store->getTriples(['subject'=>'qa-learner','object'=>$subject]))===1,'duplicate student relationship');
  verify(count($store->getNestedTriples(['subject'=>$subject])[$subject]['isMentorOf'])===2,'nested relationships');
  $node=$store->__get($subject);verify(count($node->isMentorOf->toArray())===2,'subject/predicate API');
  foreach($node->isMentorOf as $object) verify(is_string((string)$object),'Iterator PHP 8 compatibility');
  $row=$store->getTriples(['id'=>$id])[0];$store->update($id,false,false,'updated');
  $updated=$store->getTriples(['id'=>$id])[0];verify($updated['userid']===$row['userid']&&$updated['date']===$row['date'],'metadata preserved');
  for($i=0;$i<32;$i++)$store->insert($subject,'pageTest',(string)$i);
  $a=$store->browse(['subject'=>$subject],1);$b=$store->browse(['subject'=>$subject],2);
  verify(count($a['rows'])===30&&count($b['rows'])===4&&!array_intersect(array_column($a['rows'],'id'),array_column($b['rows'],'id')),'stable pagination');
  try{$store->getTriples(['id OR 1=1'=>'x']);throw new RuntimeException('bad filter accepted');}catch(InvalidArgumentException $expected){}
  verify($store->delete($id)&&$store->getTriples(['id'=>$id])===[],'delete');
  throw new LogicException('rollback-fixtures');
 });
}catch(LogicException $done){if($done->getMessage()!=='rollback-fixtures')throw $done;}
verify($store->browse([])['total']===$before,'rollback leaves database unchanged');
echo "PASS: real PDO CRUD, bound filters, nested API, Mentors compatibility/duplicates, metadata, pagination and rollback.\n";
