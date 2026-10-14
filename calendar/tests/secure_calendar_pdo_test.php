<?php
/** Local integration test: dbTable fixtures, synthetic actor and CSRF boundary adapter. */
if(PHP_SAPI!=='cli'||getenv('CHISIMBA_CALENDAR_TEST')!=='1')exit("Set CHISIMBA_CALENDAR_TEST=1 locally.\n");
$GLOBALS['kewl_entry_point_run']=true;$_SERVER['REQUEST_METHOD']='CLI';$_SERVER['HTTP_HOST']='chisimba.test:8445';$_SERVER['SCRIPT_NAME']='/ch/index.php';$_SERVER['QUERY_STRING']='';require 'classes/core/engine_class_inc.php';$engine=new engine();
set_error_handler(static function($n,$s){if(error_reporting()&$n)throw new RuntimeException($s);return false;});
$engine->getObject('calendareditor','calendar');$repo=$engine->getObject('dbcalendarentries','calendar');
class FixtureCalendarEditor extends calendareditor {
    public $actor,$course;
    public function getObject($name,$module=''){if($name==='user')return $this->actor;if($name==='dbcontext')return $this->course;return parent::getObject($name,$module);}
    public function csrf(){return new class{function consume($purpose,$token){return $token==='valid';}};}
}
class FailingCalendarEntries extends dbcalendarentries {
    private int $count=0;
    public function update($field,$id,$values,$table=''){if(++$this->count===2)throw new RuntimeException('injected-second-write');return parent::update($field,$id,$values,$table);}
}
$service=new FixtureCalendarEditor($engine,'calendar');
$service->actor=new class{public string $id='qa-calendar-owner';public bool $logged=true,$admin=false,$teacher=false;function userId(){return $this->id;}function isLoggedIn(){return $this->logged;}function isAdmin(){return $this->admin;}function isContextLecturer($uid,$code){return $this->teacher&&$code==='qa-course';}};
$service->course=new class{function getContextCode(){return 'qa-course';}function getContextDetails($code){return $code==='qa-course'?['contextcode'=>$code]:false;}};
function check($ok,$message){if(!$ok)throw new RuntimeException($message);}
function rejected($callback,$code){try{$callback();throw new LogicException('Unexpected success');}catch(RuntimeException $e){check($e->getMessage()===$code,'Expected '.$code.' got '.$e->getMessage());}}
$id=bin2hex(random_bytes(16));$input=['id'=>'','revision'=>'','contextcode'=>'qa-course','csrf_token'=>'valid','eventfor'=>'0','date'=>'2026-10-14','date2'=>'2026-10-16','title'=>'QA security fixture','details'=>'<p>Safe <script>bad()</script>details</p>','url'=>'https://example.invalid/','timefrom'=>'09:00','timeto'=>'10:00'];
try{$repo->transaction(function()use($repo,$service,$engine,$id,$input){
    rejected(fn()=>$service->mutate('saveevent',$input,'GET',$id),'calendar_method');
    $bad=$input;$bad['csrf_token']='expired';rejected(fn()=>$service->mutate('saveevent',$bad,'POST',$id),'calendar_csrf');
    $service->actor->logged=false;rejected(fn()=>$service->mutate('saveevent',$input,'POST',$id),'calendar_denied');$service->actor->logged=true;
    foreach(['1','2','3'] as $scope){$bad=$input;$bad['eventfor']=$scope;rejected(fn()=>$service->mutate('saveevent',$bad,'POST',$id),'calendar_denied');}
    $bad=$input;$bad['contextcode']='another-course';rejected(fn()=>$service->mutate('saveevent',$bad,'POST',$id),'calendar_stale');
    $bad=$input;$bad['date2']='2026-02-30';rejected(fn()=>$service->mutate('saveevent',$bad,'POST',$id),'calendar_invalid');
    check($service->mutate('saveevent',$input,'POST',$id)===$id,'create root ID');$rows=$repo->batch($id);check(count($rows)===3,'three-day series');check(!str_contains($rows[0]['eventdetails'],'<script'),'sanitisation');
    rejected(fn()=>$service->mutate('saveevent',$input,'POST',$id),'calendar_stale');
    $draft=$service->load($rows[1]['id']);check($draft['id']===$id&&$draft['date2']==='2026-10-16','child resolves whole series');
    $edit=array_merge($input,$draft);$edit['title']='Changed';
    $service->actor->id='outsider';rejected(fn()=>$service->load($id),'calendar_denied');rejected(fn()=>$service->mutate('updateevent',$edit,'POST'),'calendar_denied');rejected(fn()=>$service->mutate('delete',$edit,'POST'),'calendar_denied');$service->actor->id='qa-calendar-owner';
    $failing=new FailingCalendarEntries($engine,'calendar');$before=$repo->revision($repo->batch($id));
    rejected(fn()=>$failing->saveSeries(['eventtitle'=>'Partial'],['2026-10-14','2026-10-15','2026-10-16'],$id,$before,'',fn($row)=>$service->mayManage($row)),'injected-second-write');check($repo->revision($repo->batch($id))===$before,'second-write rollback');
    $attachments=$engine->getObject('dbeventattachments','calendarbase');$aid=$attachments->insert(['event_id'=>$rows[2]['id'],'attachment_id'=>'qa-file-preserved','userId'=>'qa-calendar-owner']);
    rejected(fn()=>$service->mutate('updateevent',$edit,'POST'),'calendar_stale');
    $edit=array_merge($input,$service->load($id));$edit['date2']='2026-10-14';$edit['eventfor']='3';
    $service->mutate('updateevent',$edit,'POST');$current=$repo->batch($id);check(count($current)===1&&$current[0]['id']===$id&&(string)$current[0]['userorcontext']==='0','shrink retains root and immutable scope');check($attachments->getRow('id',$aid)['event_id']===$id,'child attachment reference retained');
    $courseRow=['userorcontext'=>'1','context'=>'qa-course','userfirstentry'=>'someone'];check(!$service->mayManage($courseRow),'student denied');$service->actor->teacher=true;check($service->mayManage($courseRow),'instructor allowed');$service->actor->teacher=false;check(!$service->mayManage($courseRow),'revoked instructor denied');
    $siteRow=$courseRow;$siteRow['context']='root';check(!$service->mayManage($siteRow),'site denied');$service->actor->admin=true;check($service->mayManage($siteRow),'site admin allowed');$siteRow['moduleevent_table']='assessment';check(!$service->mayManage($siteRow),'module-owned event protected');$service->actor->admin=false;
    // Exercise real course/site persistence as well as permission predicates.
    foreach(['1','3'] as $scope){
        $service->actor->teacher=$scope==='1';$service->actor->admin=$scope==='3';
        $scoped=$input;$scoped['eventfor']=$scope;$scopedId=bin2hex(random_bytes(16));
        $service->mutate('saveevent',$scoped,'POST',$scopedId);$saved=$repo->batch($scopedId);
        check($saved[0]['context']===($scope==='1'?'qa-course':'root'),'persisted calendar scope');
        $change=array_merge($scoped,$service->load($scopedId));$change['title']='Permitted scope update';$service->mutate('updateevent',$change,'POST');
        $change=array_merge($change,$service->load($scopedId));$service->actor->teacher=false;$service->actor->admin=false;
        rejected(fn()=>$service->mutate('delete',$change,'POST'),'calendar_denied');
        $service->actor->teacher=$scope==='1';$service->actor->admin=$scope==='3';$service->mutate('delete',$change,'POST');
    }
    $service->actor->teacher=false;$service->actor->admin=false;
    $delete=array_merge($input,$service->load($id));$service->mutate('delete',$delete,'POST');check(!$repo->getRow('id',$id)&&!$attachments->getRow('id',$aid),'authorised delete removes records, not file bytes');
    throw new LogicException('rollback-fixtures');
});}catch(LogicException $e){if($e->getMessage()!=='rollback-fixtures')throw $e;}
check(!$repo->getRow('id',$id),'fixture rollback');echo "PASS secure Calendar scope/roles, CSRF/methods, stable series IDs, stale/replay guards, attachment-reference retention, failure rollback and deletion\n";
