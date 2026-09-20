<?php
/** Standalone question authoring and offline downloads. @author Derek Keats */
if(empty($GLOBALS['kewl_entry_point_run']))die('No direct access');
class mcqgenerator extends controller
{
    private $csrf;
    public function init(){$this->csrf=$this->getObject('nativeauthwebcomposition','security')->build()['csrf'];}
    public function requiresLogin($action=null){return true;}
    private function param($key,$default=''){$v=$this->getParam($key,$default);return is_string($v)?trim($v):'';}
    public function dispatch($action=null)
    {
        header('Cache-Control: private, no-store');
        $error='';$row=null;$source=$this->param('source');$title=$this->param('title');
        $policy=$this->getObject('workshoppolicy');$store=$this->getObject('workshopstore');$service=$this->getObject('workshopservice');
        $action=$this->param('action','list');$id=$this->param('id');
        $sessionCount=(int)$this->getSession('question_count',10);
        if($sessionCount<1||$sessionCount>30)$sessionCount=10;
        $owner=(string)$this->getObject('user','security')->userId();
        if(!$policy->allowed()){http_response_code(403);$this->setVar('workshopError','forbidden');return 'denied_tpl.php';}
        if($action==='formtoken'){
            // A custom-header same-origin request can renew CSRF without replaying a mutation.
            if(($_SERVER['REQUEST_METHOD']??'')!=='POST'
                || ($_SERVER['HTTP_X_CHISIMBA_FORM']??'')!=='mcqgenerator'
                || ($_SERVER['HTTP_SEC_FETCH_SITE']??'')!=='same-origin'){
                http_response_code(403);exit;
            }
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode(['token'=>$this->csrf->issueForSession('mcqgenerator_write')]);exit;
        }
        if(str_starts_with($action,'exam'))return $this->exams($action,$id,$owner);
        $workspace=null;$examid=$this->param('examid');
        try {
            if($id!==''){$existing=$service->read($id);$examid=$existing['examid'];}
            if($examid!=='')$workspace=$this->getObject('examservice')->read($examid);
            elseif($id===''&&$action!=='create')return $this->nextAction('exams');
            if(in_array($action,['create','derive','generate','generatepart','more','save','import','delete'],true)){
                if(($_SERVER['REQUEST_METHOD']??'')!=='POST'||!$this->csrf->consume('mcqgenerator_write',$this->param('csrf_token')))throw new DomainException('expired');
                if($action==='create'){
                    if(!$workspace)throw new DomainException('not_found');
                    $title=$service->title($title);$extract=$this->getObject('workshopsource');
                    $upload=$_FILES['sourcefile']??[];
                    if(($upload['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_NO_FILE){
                        if($source!=='')throw new DomainException('choose_source');
                        $source=$extract->upload($upload);
                    }else{
                        try{$source=$extract->validate($source);}
                        catch(DomainException $e){throw new DomainException($e->getMessage()==='source_short'?'source_short_no_file':$e->getMessage());}
                    }
                    $count=$this->param('count',(string)$sessionCount);
                    if(!preg_match('/^(?:[1-9]|[12][0-9]|30)$/D',$count))throw new DomainException('count_invalid');
                    $this->setSession('question_count',(int)$count);
                    $id=$store->createSet($owner,$title,$source,[],(int)$count,$workspace['id'],$this->param('question_type','mcq'));
                    return $this->nextAction('view',['id'=>$id]);
                }
                $row=$service->read($id);
                if($action==='derive'){
                    if((string)$row['version']!==$this->param('version'))throw new DomainException('changed');
                    $count=$this->param('count',(string)$sessionCount);
                    if(!preg_match('/^(?:[1-9]|[12][0-9]|30)$/D',$count))throw new DomainException('count_invalid');
                    $new=$store->createSet($owner,$service->title($title),$row['source_text'],[],(int)$count,$row['examid'],'short_answer');
                    return $this->nextAction('view',['id'=>$new]);
                }
                if($action==='delete'){
                    if($this->param('confirm_delete')!=='1')throw new DomainException('delete_confirm_required');
                    if((string)$row['version']!==$this->param('version'))throw new DomainException('changed');
                    $store->removeOwned($row['id'],$owner,(int)$row['version']);
                    return $this->nextAction('list',['examid'=>$examid]);
                }
                if($action==='import'){
                    $context=(string)$this->getObject('dbcontext','context')->getContextCode();
                    if($context!==$this->param('contextcode'))throw new DomainException('course_forbidden');
                    $service->importCourse($id,$context);
                    return $this->nextAction('view',['id'=>$id]);
                }
                if($action==='more'){
                    if($this->param('consent')!=='1')throw new DomainException('consent_required');
                    $count=$this->param('additional');
                    if(!preg_match('/^(?:[1-9]|[12][0-9]|30)$/D',$count))throw new DomainException('more_limit');
                    $service->more($id,(int)$count,$this->param('version'));
                    return $this->nextAction('view',['id'=>$id]);
                }
                if($action==='generatepart'){$service->part($id);return $this->nextAction('view',['id'=>$id]);}
                if($action==='generate'){
                    if($this->param('consent')!=='1')throw new DomainException('consent_required');
                    $count=$this->param('count',(string)$row['question_count']);
                    if(!preg_match('/^(?:[1-9]|[12][0-9]|30)$/D',$count))throw new DomainException('count_invalid');
                    $service->begin($id,(int)$count);
                    $this->setSession('question_count',(int)$count);
                }else{
                    if(empty(json_decode($row['questions_json'],true)))throw new DomainException('questions_invalid');
                    if((string)$row['version']!==$this->param('version'))throw new DomainException('changed');
                    $questions=$service->review($this->getParam('questions',[]),count(json_decode($row['questions_json'],true)),$row['question_type']??'mcq');
                    $store->saveSet($row,$service->title($title),$questions,$this->param('reviewed')==='1');
                }
                return $this->nextAction('view',['id'=>$id]);
            }
            if(in_array($action,['view','download'],true)){
                $row=$service->read($id);
                if($action==='download'){
                    if(!json_decode($row['questions_json'],true))throw new DomainException('questions_invalid');
                    $format=$this->param('format');if(!in_array($format,['txt','odt'],true))throw new DomainException('file_type');
                    $export=$this->getObject('workshopexport');$answers=$this->param('answers')==='1';
                    $row['questions_json']=json_encode($service->selected(json_decode($row['questions_json'],true)),JSON_THROW_ON_ERROR);
                    $body=$format==='odt'?$export->odt($row,$answers):$export->text($row,$answers);
                    header('X-Content-Type-Options: nosniff');header('Content-Type: '.($format==='odt'?'application/vnd.oasis.opendocument.text':'text/plain; charset=UTF-8'));
                    header('Content-Disposition: '.$export->disposition($row,$format,$answers));echo $body;exit;
                }
            }
        }catch(DomainException $e){
            $error=$e->getMessage();
            if(in_array($action,['generate','generatepart'],true) && $row)$row=$service->read($id);
            http_response_code($error==='not_found'?404:422);
            if($error==='expired' && $id!==''){
                try{$row=$service->read($id);}catch(DomainException $ignored){}
            }
        }
        catch(Throwable $e){$error='storage_failed';http_response_code(500);}
        $this->setVar('workshopExam',$workspace);
        $this->setVar('workshopError',$error);$this->setVar('workshopRow',$row);
        $this->setVar('workshopCount',$this->param('count',(string)$sessionCount));$this->setVar('workshopTitle',$title);$this->setVar('workshopSource',$source);
        $this->setVar('workshopToken',$this->csrf->issueForSession('mcqgenerator_write'));
        $page=max(1,(int)$this->param('page','1'));
        $this->setVar('workshopPage',$page);$this->setVar('workshopSets',$row?[]:($workspace?$store->owned($owner,$page,$workspace['id']):[]));
        $this->setVar('workshopDraft', $error!==''&&$action==='save'?$this->getParam('questions',[]):null);
        return 'workspace_tpl.php';
    }

    /** Exam assembly has its own private store but shares authorisation, CSRF and exports. */
    private function exams($action,$id,$owner)
    {
        $service=$this->getObject('examservice');$store=$this->getObject('examstore');
        $row=null;$chapter=null;$error='';$draft=null;
        try {
            if(in_array($action,['examcreate','examsave','examadd','examdelete'],true)){
                if(($_SERVER['REQUEST_METHOD']??'')!=='POST'||!$this->csrf->consume('mcqgenerator_write',$this->param('csrf_token')))throw new DomainException('expired');
                if($action==='examcreate'){
                    $id=$store->createExam($owner,$this->getObject('workshopservice')->title($this->param('title')),$service->emptyContent());
                    return $this->nextAction('new',['examid'=>$id]);
                }
                $row=$service->read($id);$service->revision($row,$this->param('version'));
                if($action==='examdelete'){
                    if($this->param('confirm_delete')!=='1')throw new DomainException('delete_confirm_required');
                    $store->removeOwned($row);return $this->nextAction('exams');
                }
                if($action==='examadd'){
                    $chapter=$this->getObject('workshopservice')->read($this->param('setid'));
                    if(($chapter['examid']??'')!==$row['id']){$chapter=null;throw new DomainException('exam_wrong_chapter');}
                    if((string)$chapter['version']!==$this->param('setversion'))throw new DomainException('exam_source_changed');
                    $content=$service->add($row,$chapter,$this->getParam('selected',[]));$title=$row['title'];
                }else{
                    $content=$service->edit($row,$this->getParam('exam',[]));$title=$this->getObject('workshopservice')->title($this->param('title'));
                }
                $store->saveExam($row,$title,$content);
                return $this->nextAction('examview',['id'=>$id,'saved'=>'1','page'=>max(1,(int)$this->param('page','1'))]);
            }
            if(in_array($action,['examview','examdownload'],true)){
                $row=$service->read($id);
                if($action==='examdownload'){
                    $answers=$this->param('answers')==='1';$export=$this->getObject('workshopexport');
                    $body=$service->odt($row,$answers);
                    header('X-Content-Type-Options: nosniff');header('Content-Type: application/vnd.oasis.opendocument.text');
                    header('Content-Disposition: '.$export->disposition($row,'odt',$answers,$answers?'-marking-sheet':'-questions'));echo $body;exit;
                }
                if($this->param('setid')!==''){
                    $chapter=$this->getObject('workshopservice')->read($this->param('setid'));
                    if(($chapter['examid']??'')!==$row['id']){$chapter=null;throw new DomainException('exam_wrong_chapter');}
                }
            }
        }catch(DomainException $e){
            $error=$e->getMessage();http_response_code($error==='not_found'?404:422);
            if($id!==''&&!$row){try{$row=$service->read($id);}catch(DomainException $ignored){}}
            if($action==='examsave')$draft=$this->getParam('exam',[]);
        }catch(Throwable $e){$error='storage_failed';http_response_code(500);}
        $page=max(1,(int)$this->param('page','1'));
        $this->setVar('examRow',$row);$this->setVar('examChapter',$chapter);$this->setVar('examError',$error);
        $this->setVar('examDraft',$draft);$this->setVar('examTitle',$this->param('title'));
        $this->setVar('examToken',$this->csrf->issueForSession('mcqgenerator_write'));
        $this->setVar('examPage',$page);$this->setVar('examRows',$row?[]:$store->owned($owner,$page));
        $this->setVar('examSets',$row?$this->getObject('workshopstore')->examChapters($owner,$page,$row['id']):[]);
        return 'exams_tpl.php';
    }
}
