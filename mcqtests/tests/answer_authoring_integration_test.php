<?php
/** Run only against the disposable local rehearsal. No real course data is edited. */
if (PHP_SAPI !== 'cli' || getenv('CHISIMBA_DISPOSABLE_TEST') !== '1') exit(64);
chdir('/var/www/html/ch');
if (!str_contains(file_get_contents('config/dbdetails_inc.php'), '@editor-db/editor_fixture')) exit(64);
$GLOBALS['kewl_entry_point_run']=true;
$_SERVER+=array('REQUEST_METHOD'=>'CLI','HTTP_HOST'=>'127.0.0.1:8088','SCRIPT_NAME'=>'/ch/index.php','QUERY_STRING'=>'');
require 'classes/core/engine_class_inc.php';$engine=new engine();
function check($condition,$label){if(!$condition)throw new RuntimeException($label);echo 'PASS '.$label."\n";}
$tests=$engine->getObject('dbtestadmin','mcqtests');$questions=$engine->getObject('dbquestions','mcqtests');$answers=$engine->getObject('dbanswers','mcqtests');
$id=$tests->insert(array('context'=>'nativeqa','name'=>'Answer security fixture','status'=>'inactive','testtype'=>'Formative'));
$q=$questions->insert(array('testid'=>$id,'question'=>'Synthetic','questiontype'=>'mcq','mark'=>1,'questionorder'=>1));
$draft=array('answer1'=>'Two','answer2'=>'Four','comment1'=>'Good','comment2'=>'Try again','correctans'=>'1');
try {
    $fields=array('testid'=>$id,'question'=>'Edited synthetic question','hint'=>'','mark'=>'2','questiontype'=>'mcq');
    $questions->saveStandardQuestion('nativeqa',$id,$q,$fields);
    check((float)$tests->getRow('id',$id)['totalmark']===2.0,'question and test total save together');
    $answers->saveAnswerSet('nativeqa',$id,$q,$draft,2);
    $before=$answers->selectPrepared('SELECT * FROM tbl_test_answers WHERE questionid=? ORDER BY answerorder',array($q));
    check(count($before)===2,'complete answer set saved');
    $draft['answer1']='Two wings';$answers->saveAnswerSet('nativeqa',$id,$q,$draft,2);
    $after=$answers->selectPrepared('SELECT * FROM tbl_test_answers WHERE questionid=? ORDER BY answerorder',array($q));
    check(array_column($before,'id')===array_column($after,'id'),'answer identities preserved');
    foreach(array(array('wrong',$id,$q),array('nativeqa',$id,'missing')) as $target) {
        try {$answers->saveAnswerSet(...array_merge($target,array($draft,2)));throw new LogicException('Expected rejection');}
        catch(DomainException $error){check(true,'foreign or missing target denied');}
    }
    $bad=$draft;$bad['answer1']='Must roll back';$bad['answer2']=array('invalid');
    try {$answers->saveAnswerSet('nativeqa',$id,$q,$bad,2);throw new LogicException('Expected rejection');}
    catch(DomainException $error){check(true,'invalid second answer rejected');}
    check($after===$answers->selectPrepared('SELECT * FROM tbl_test_answers WHERE questionid=? ORDER BY answerorder',array($q)),'partial first answer update rolled back');
    $answers->executePrepared("CREATE TRIGGER nativeqa_answer_failure BEFORE UPDATE ON tbl_test_answers FOR EACH ROW BEGIN IF NEW.answerorder=2 THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='fixture failure'; END IF; END");
    try {
        $changed=$draft;$changed['answer1']='Storage failure must roll back';
        try {$answers->saveAnswerSet('nativeqa',$id,$q,$changed,2);throw new LogicException('Expected storage failure');}
        catch (RuntimeException $error) {check(!($error instanceof LogicException),'storage failure reported');}
        check($after===$answers->selectPrepared('SELECT * FROM tbl_test_answers WHERE questionid=? ORDER BY answerorder',array($q)),'storage failure rolls back all answer changes');
    } finally {$answers->executePrepared('DROP TRIGGER nativeqa_answer_failure');}
    $answers->selectCorrectAnswer('nativeqa',$id,$q,$after[1]['id']);
    check((int)$answers->getRow('id',$after[1]['id'])['correct']===1 && (int)$answers->getRow('id',$after[0]['id'])['correct']===0,'correct-answer selection is exclusive');
    $tests->update('id',$id,array('status'=>'open'));
    try {$answers->saveAnswerSet('nativeqa',$id,$q,$draft,2);throw new LogicException('Expected rejection');}
    catch(DomainException $error){check(true,'open quiz answer mutation denied');}
} finally {$answers->delete('questionid',$q);$questions->delete('id',$q);$tests->delete('id',$id);}
