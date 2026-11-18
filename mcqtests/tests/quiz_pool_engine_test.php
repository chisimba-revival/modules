<?php
/** Included by the disposable full-engine rehearsal; synthetic questions only. */
if(PHP_SAPI!=='cli'||getenv('LOCAL_MAP_RELEASE')!=='1'||($dbName??'')!=='chisimba_map_rehearsal')exit(64);
$pool=$engine->getObject('quizbankpool','mcqtests');$bankstore=$engine->getObject('bankstore','mcqtests');
$config=$pool->config($testId);
check($config && (int)$config['bank_count']===30 && (int)$config['draw_count']===10 && $config['selection']==='per_attempt','Chapter bank configuration');
check(count($bankstore->items($config['bankid']))===30,'Thirty independent bank items');
check($config['chapterid']===$state['tests'][0]['chapter'],'Bank linked to chapter');
check(count($bankstore->metadata($state['questions'][0]['id']))>1,'Test copy retains bank provenance');
check($stack['sessions']->establish($uid,['username'=>$username,'provider'=>'database_local']),'Restore fixture session');
$results=$engine->getObject('dbresults','mcqtests');
$newAttempt=fn($test)=>$results->addResult(['testid'=>$test,'studentid'=>$uid,'mark'=>-1]);
$resultId=$newAttempt($testId);$paper=$pool->ensurePaper($resultId,$uid,$testId);
check(count($paper['questions'])===10 && count(array_unique(array_column($paper['questions'],'id')))===10 && $paper['totalmark']===10,'Ten distinct questions and ten marks');
check($pool->ensurePaper($resultId,$uid,$testId)===$paper,'Resume is immutable');
check(count($pool->page($paper,$resultId,0,6))===6&&count($pool->page($paper,$resultId,6,6))===4,'Stable pagination');
try{$pool->ensurePaper($resultId,'another-user',$testId);throw new LogicException('Unauthorised draw');}catch(DomainException $e){check($e->getMessage()==='forbidden','Foreign attempt rejected');}
$other=$pool->ensurePaper($newAttempt($testId),$uid,$testId);
// Exercise many draws without assuming any two random outcomes must differ.
for($i=0;$i<10;$i++){ $draw=$pool->sample($state['questions'],10);check(count(array_unique(array_column($draw,'id')))===10,'Samples without replacement'); }
$marked=$engine->getObject('dbmarked','mcqtests');
foreach($paper['questions'] as $q){$answer=array_values(array_filter($q['answers'],fn($a)=>(int)$a['correct']===1))[0];$marked->addMarkedForAttempt(['testid'=>$testId,'questionid'=>$q['id'],'answerid'=>$answer['id'],'studentid'=>$uid,'resultid'=>$resultId,'answered'=>'']);}
$outside=array_values(array_filter($state['questions'],fn($q)=>!in_array($q['id'],array_column($paper['questions'],'id'),true)))[0];
$answer=$maps->selectPrepared('SELECT id FROM tbl_test_answers WHERE questionid=? AND correct=1',[$outside['id']])[0];
$marked->addMarkedForAttempt(['testid'=>$testId,'questionid'=>$outside['id'],'answerid'=>$answer['id'],'studentid'=>$uid,'resultid'=>$resultId,'answered'=>'']);
check($pool->grade($paper,$resultId)===10,'Unselected question cannot inflate grade');
$maps->executeExactPrepared('UPDATE tbl_test_answers SET correct=0 WHERE testid=?',[$testId]);
check($pool->grade($pool->paper($resultId,$uid,$testId),$resultId)===10,'Saved answer key survives later author edits');
check(count($pool->review($paper,$resultId,0))===10,'Review only selected questions');
check($engine->getObject('dbquestions','mcqtests')->sumTotalmark($testId)===10,'Overview denominator is draw size');
// A separate fixed quiz uses one saved draw, while the full bank remains reusable.
$fixedId=$maps->createMap(['title'=>'Fixed quiz fixture','ownerid'=>$uid,'scopetype'=>'personal','scopeid'=>$uid,'rootnodeid'=>$doc['rootId']]);
foreach($doc['nodes'] as $node){if($node['type']==='quiz')$node['presentation']['quizSettings']=['bankCount'=>30,'drawCount'=>10,'selection'=>'fixed'];$nodes->addNode($fixedId,$node);}
foreach($doc['relationships'] as $edge){$edge['id']=bin2hex(random_bytes(16));$edges->addRelationship($fixedId,$edge);}
$fixedCode='mapfixed'.bin2hex(random_bytes(4));$service->generate($fixedId,1,$fixedCode);
$stack['sessions']->destroy();for($i=0;$i<30;$i++){if(!$jobs->runOne()['selected'])break;}
$fixedState=$engine->getObject('quizoutlineservice','mcqtests')->snapshot($fixedCode);$fixedTest=$fixedState['tests'][0]['id'];$fixedConfig=$pool->config($fixedTest);
check(count($fixedState['questions'])===10 && count($bankstore->items($fixedConfig['bankid']))===30,'Fixed draw copies ten from thirty bank questions');
check($stack['sessions']->establish($uid,['username'=>$username,'provider'=>'database_local']),'Author session for fixed attempts');
$a=$pool->ensurePaper($newAttempt($fixedTest),$uid,$fixedTest);$b=$pool->ensurePaper($newAttempt($fixedTest),$uid,$fixedTest);
$aIds=array_column($a['questions'],'id');$bIds=array_column($b['questions'],'id');sort($aIds);sort($bIds);check($aIds===$bIds,'Fixed selection shared across attempts');
// Publication rollback composes with an outer savepoint, including the bank.
$beforeBanks=count($bankstore->banks());$scratch=bin2hex(random_bytes(16));$fields=$fixedState['tests'][0];$fields['id']=$scratch;unset($fields['puid']);$maps->insert($fields,'tbl_tests');
$candidates=json_decode($maps->selectPrepared('SELECT result_json FROM tbl_mcq_quiz_input_jobs WHERE testid=?',[$fixedTest])[0]['result_json'],true);
try{$maps->transactionWithSavepoint(function()use($pool,$fixedCode,$uid,$scratch,$candidates){$pool->publish(['id'=>bin2hex(random_bytes(16)),'testid'=>$scratch,'contextcode'=>$fixedCode,'userid'=>$uid],$candidates,['bankCount'=>30,'drawCount'=>10,'selection'=>'fixed']);throw new RuntimeException('rollback_fixture');});}catch(RuntimeException $e){check($e->getMessage()==='rollback_fixture','Expected rollback');}
check(count($bankstore->banks())===$beforeBanks && !$pool->config($scratch) && !$maps->selectPrepared('SELECT id FROM tbl_test_questions WHERE testid=?',[$scratch]),'Atomic bank, copies and configuration rollback');
echo "PASS quiz pools: linked bank, fixed and per-attempt draws, durable resume, pagination, ownership, snapshot grading, bounded scores, bank metadata and atomic rollback\n";
