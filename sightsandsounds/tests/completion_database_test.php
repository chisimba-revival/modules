<?php
/** Local installed-database test; removes only its unpredictable synthetic scope. */
if(PHP_SAPI!=='cli'||getenv('SS_DATABASE_TEST')!=='1')exit(64);
chdir('/var/www/html/ch');
$_SERVER['REQUEST_METHOD']='CLI';$_SERVER['HTTP_HOST']='chisimba.test:8445';$_SERVER['SCRIPT_NAME']='/ch/index.php';$_SERVER['QUERY_STRING']='';
$GLOBALS['kewl_entry_point_run']=true;require 'classes/core/engine_class_inc.php';$e=new engine();
if(parse_url($e->getObject('altconfig','config')->getSiteRoot(),PHP_URL_HOST)!=='chisimba.test')throw new RuntimeException('Local site required');
$bank=$e->getObject('practicebank','sightsandsounds');$runs=$e->getObject('practiceruns','sightsandsounds');$receipts=$e->getObject('practicecompletions','sightsandsounds');$e->getObject('practicerules','sightsandsounds');
$scope='ss-test-'.bin2hex(random_bytes(12));
function expect($ok,$why){if(!$ok)throw new RuntimeException($why);}
function rejected($fn,$message){try{$fn();throw new RuntimeException('Accepted '.$message);}catch(DomainException $e){}}
try {
    foreach(range(1,3) as $i)$bank->addItem($scope,'fixture',['answer'=>'African fish eagle','alternatives'=>'Haliaeetus vocifer','feedback'=>'','credit'=>'Synthetic'],'fixture','image');
    $activity=$bank->publish($scope,'fixture',['title'=>'Fixture','instructions'=>'','questioncount'=>'3']);
    $otherActivity=$bank->publish($scope,'fixture',['title'=>'Second instance','instructions'=>'','questioncount'=>'2']);
    $a=$bank->activity($scope,$activity);$run=$runs->start($scope,'fixture',$a);$row=$runs->owned($scope,'fixture',$run);$state=json_decode($row['state'],true);$qid=array_key_first($state);
    expect($runs->start($scope,'fixture',$a)===$run,'Resume changed paper');
    $runs->checkAnswer($scope,'fixture',$run,$qid,'wrong',0);
    rejected(fn()=>$runs->checkAnswer($scope,'fixture',$run,$qid,'African fish eagle',0),'stale check');
    $runs->checkAnswer($scope,'fixture',$run,$qid,'Haliaeetus vocifer',1);
    $answers=array_map(fn($v)=>['answer'=>'African fish eagle','checks'=>2],$state);
    rejected(fn()=>$receipts->finish($scope,'outsider',$run,2,$answers),'foreign owner');
    rejected(fn()=>$receipts->finish('foreign','fixture',$run,2,$answers),'foreign scope');
    rejected(fn()=>$receipts->finish($scope,'fixture',$run,0,$answers),'stale result');
    rejected(fn()=>$receipts->finish($scope,'fixture',$run,2,['foreign'=>['answer'=>'x','checks'=>1]]),'foreign paper');
    expect(!$receipts->forRun($run),'Failed submission created receipt');
    $receipt=$receipts->finish($scope,'fixture',$run,2,$answers);
    expect((int)$receipt['correct']===3,'Incorrect scoring');
    expect($receipts->finish($scope,'fixture',$run,2,$answers)['id']===$receipt['id'],'Duplicate receipt');
    $changed=$answers;$changed[$qid]['answer']='wrong';rejected(fn()=>$receipts->finish($scope,'fixture',$run,2,$changed),'conflicting retry');
    rejected(fn()=>$runs->checkAnswer($scope,'fixture',$run,$qid,'wrong',3),'editing completed run');
    expect($runs->start($scope,'fixture',$a)!==$run,'Completed run resumed');
    $provider=$e->getObject('sightsandsoundsassessmentprovider','sightsandsounds');
    expect(count($provider->listActivities($scope))===2,'Shared bank instances');
    expect($provider->getStudentResult($scope,$activity,'fixture')['mark_percent']==100,'Provider result');
    expect($provider->getStudentResult($scope,$otherActivity,'fixture')['status']==='not_attempted','Results leaked between instances');
    echo "PASS PDO completion, ownership, stale saves, immutable receipts, replay/conflict, instance isolation and provider results\n";
} finally {
    $receipts->delete('contextcode',$scope);$runs->delete('contextcode',$scope);
    $bank->executePrepared('DELETE FROM tbl_ss_activities WHERE contextcode = ?',[$scope]);
    $bank->delete('contextcode',$scope);
}
