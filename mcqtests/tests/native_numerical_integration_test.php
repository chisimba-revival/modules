<?php
/** Run only against the disposable local rehearsal. No real course data is edited. */
if (PHP_SAPI !== 'cli' || getenv('CHISIMBA_DISPOSABLE_TEST') !== '1') exit(64);
chdir('/var/www/html/ch');
if (!str_contains(file_get_contents('config/dbdetails_inc.php'), '@editor-db/editor_fixture')) exit(64);
$GLOBALS['kewl_entry_point_run']=true;
$_SERVER+=array('REQUEST_METHOD'=>'CLI','HTTP_HOST'=>'127.0.0.1:8088','SCRIPT_NAME'=>'/ch/index.php','QUERY_STRING'=>'');
require 'classes/core/engine_class_inc.php';$engine=new engine();
function check($condition,$label){if(!$condition)throw new RuntimeException($label);echo 'PASS '.$label."\n";}
$db=$engine->getObject('dbtestadmin','mcqtests');$service=$engine->getObject('numericalauthoring','mcqtests');
$id=$db->insert(array('context'=>'nativeqa','name'=>'Disposable numerical test','status'=>'inactive','testtype'=>'Advanced','totalmark'=>0));
$draft=array('qName'=>'Round trip','qText'=>'<p>How many?</p>','qMark'=>'2','qPenalty'=>'0','aNumerical1'=>'2','mark_1'=>'2','aNumerical2'=>'','aNumerical3'=>'','unitmode'=>'unmarked','dispUnit'=>'yes','aUnit'=>'wings','instructions'=>'<p>Use a number.</p>');
try {
    $qid=$service->save('nativeqa',$id,'',$draft);
    check((bool)$qid,'numerical question saves');
    check(!$db->getTests2('wrongcourse','*',$id),'test ID cannot bypass course scope');
    check(!$db->getTests2("nativeqa' OR 1=1 --",'*',$id),'course values remain bound');
    $options=$service->selectPrepared('SELECT * FROM tbl_test_question_numericaloptions WHERE questionid=?',array($qid));
    check($options[0]['unitgradingtype']==='no' && $options[0]['showunits']==='yes','unit choices persist');
    check((float)$db->getRow('id',$id)['totalmark']===2.0,'test total refreshed');
    foreach(array(['wrongcourse',$id,$qid],['nativeqa',$id,'missing']) as $target) {
        try {$service->save(...array_merge($target,[$draft]));throw new RuntimeException('Expected denial');}
        catch(DomainException $error){check($error->getMessage()==='forbidden','reject wrong course or question');}
    }
    $db->update('id',$id,array('status'=>'open'));
    try {$service->save('nativeqa',$id,$qid,$draft);throw new RuntimeException('Expected denial');}
    catch(DomainException $error){check($error->getMessage()==='forbidden','open test immutable');}
    $db->update('id',$id,array('status'=>'inactive'));
    $before=$service->selectPrepared('SELECT * FROM tbl_test_questions WHERE id=?',array($qid));
    $bad=$draft;$bad['aNumerical1']='not a number';
    try {$service->save('nativeqa',$id,$qid,$bad);throw new RuntimeException('Expected validation');}
    catch(DomainException $error){check($error->getMessage()==='invalid','reject invalid numerical answer');}
    check($before===$service->selectPrepared('SELECT * FROM tbl_test_questions WHERE id=?',array($qid)),'validation leaves question unchanged');
    // A deliberately invalid column in a fixture-only trigger forces failure after the question update.
    $db->executePrepared("CREATE TRIGGER nativeqa_fail_units BEFORE INSERT ON tbl_test_question_numericalunits FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='fixture failure'");
    try {
        $changed=$draft;$changed['qName']='Must roll back';
        try {$service->save('nativeqa',$id,$qid,$changed);throw new LogicException('Expected rollback');}
        catch(RuntimeException $error){check(!($error instanceof LogicException),'storage failure reported');}
        check($before===$service->selectPrepared('SELECT * FROM tbl_test_questions WHERE id=?',array($qid)),'failed unit insert rolls back question');
        check(count($service->selectPrepared('SELECT * FROM tbl_test_question_numerical WHERE questionid=?',array($qid)))===1,'rollback preserves answers');
    } finally {$db->executePrepared('DROP TRIGGER nativeqa_fail_units');}
    $changed=$draft;$changed['qName']='Edited';$changed['qMark']='3';
    $service->save('nativeqa',$id,$qid,$changed);
    check($service->getRow('id',$qid)['name']==='Edited','edit retains question identity');
    check((float)$db->getRow('id',$id)['totalmark']===3.0,'edited total refreshed');
} finally {
    $questions=$service->selectPrepared('SELECT id FROM tbl_test_questions WHERE testid=?',array($id));
    foreach($questions as $question) foreach(array('tbl_test_question_numerical','tbl_test_question_numericaloptions','tbl_test_question_numericalunits') as $table) $service->executePrepared('DELETE FROM '.$table.' WHERE questionid=?',array($question['id']));
    $service->delete('testid',$id);$db->delete('id',$id);
}
