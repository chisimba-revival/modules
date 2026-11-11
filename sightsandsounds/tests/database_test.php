<?php
/** Opt-in integration against a disposable local database with no ss_* tables. */
if (PHP_SAPI!=='cli' || getenv('SS_DATABASE_TEST')!=='1') exit(64);
chdir('/var/www/html/ch');
$_SERVER['REQUEST_METHOD']='CLI';$_SERVER['HTTP_HOST']='chisimba.test:8445';$_SERVER['SCRIPT_NAME']='/ch/index.php';$_SERVER['QUERY_STRING']='';
$GLOBALS['kewl_entry_point_run']=true;
require 'classes/core/engine_class_inc.php';
class PracticeTestEngine extends engine {
    private $practiceServices=[];
    public function getObject($name,$moduleName) {
        if($moduleName==='sightsandsounds') {
            if(!isset($this->practiceServices[$name])) {
                require_once __DIR__.'/../classes/'.$name.'_class_inc.php';
                $this->practiceServices[$name]=new $name($this,$moduleName);
            }
            return $this->practiceServices[$name];
        }
        return parent::getObject($name,$moduleName);
    }
}
$e=new PracticeTestEngine();
if(parse_url($e->getObject('altconfig','config')->getSiteRoot(),PHP_URL_HOST)!=='chisimba.test')throw new RuntimeException('Local site required');
$schema=$e->getObject('modulesadmin','modulecatalogue');
$created=[];
function expect($ok,$message){if(!$ok)throw new RuntimeException($message);}
try {
    foreach(glob(__DIR__.'/../sql/*.sql') as $file) {
        require $file;
        if(in_array($tablename,$schema->listDbTables(),true))throw new RuntimeException('Refusing existing table '.$tablename);
        $schema->createTable($tablename,$fields,$options);$created[]=$tablename;
        foreach($tableIndexes as $name=>$definition)$schema->createTableIndex($tablename,$name,$definition);
    }
    $bank=$e->getObject('practicebank','sightsandsounds');$runs=$e->getObject('practiceruns','sightsandsounds');$e->getObject('practicerules','sightsandsounds');
    $input=['answer'=>'African fish eagle','alternatives'=>'Haliaeetus vocifer','feedback'=>'Fixture notes','credit'=>'Synthetic'];
    for($i=0;$i<10;$i++)$bank->addItem('ss-fixture','author',$input,'fixture-file-'.$i,$i%2?'audio':'image');
    expect(count($bank->items('other'))===0,'Cross-course bank leak');
    $activity=$bank->publish('ss-fixture','author',['title'=>'Fixture activity','instructions'=>'Test','questioncount'=>'10']);
    $row=$bank->activity('ss-fixture',$activity);
    $runId=$runs->start('ss-fixture','student',$row);
    expect($runs->start('ss-fixture','student',$row)===$runId,'Resume changed paper');
    $run=$runs->owned('ss-fixture','student',$runId);$state=json_decode($run['state'],true);$question=array_key_first($state);
    expect(count($state)===10,'Wrong paper size');
    foreach([['other','student'],['ss-fixture','other']] as [$course,$uid]) {
        try{$runs->owned($course,$uid,$runId);throw new RuntimeException('Run leak');}catch(DomainException $ex){}
    }
    $runs->checkAnswer('ss-fixture','student',$runId,$question,'wrong',0);
    try{$runs->checkAnswer('ss-fixture','student',$runId,$question,'African fish eagle',0);throw new RuntimeException('Stale duplicate accepted');}catch(DomainException $ex){expect($ex->getMessage()==='stale','Wrong conflict');}
    $runs->checkAnswer('ss-fixture','student',$runId,$question,'  HALIAEETUS  VOCIFER  ',1);
    $after=json_decode($runs->owned('ss-fixture','student',$runId)['state'],true);
    expect($after[$question]['correct'] && $after[$question]['firstCorrect']===false && $after[$question]['checks']===2,'Mastery history');
    expect(array_keys($after)===array_keys($state),'Paper changed');
    try{$runs->checkAnswer('ss-fixture','student',$runId,'foreign','name',2);throw new RuntimeException('Foreign question');}catch(DomainException $ex){}
    expect((int)$runs->owned('ss-fixture','student',$runId)['revision']===2,'Failed check changed revision');
    $bank->addItem('ss-fixture','author',$input,'later','image');
    expect(count(json_decode($bank->activity('ss-fixture',$activity)['itemids'],true))===10,'Published selection changed');
    $fresh=$runs->start('ss-fixture','student',$row,true);expect($fresh!==$runId,'Fresh run absent');
    echo "PASS PDO schema, persistence, scope/owner isolation, stable resume, stale duplicate, mastery and immutable activity selection\n";
} finally {
    foreach(array_reverse($created) as $table)$schema->dropTable($table);
}
