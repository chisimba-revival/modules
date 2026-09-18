<?php
/** Standalone question authoring and offline downloads. @author Derek Keats */
if(empty($GLOBALS['kewl_entry_point_run']))die('No direct access');
class questionworkshop extends controller
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
        $owner=(string)$this->getObject('user','security')->userId();
        if(!$policy->allowed()){http_response_code(403);$this->setVar('workshopError','forbidden');return 'denied_tpl.php';}
        if($action==='formtoken'){
            // A custom-header same-origin request can renew CSRF without replaying a mutation.
            if(($_SERVER['REQUEST_METHOD']??'')!=='POST'
                || ($_SERVER['HTTP_X_CHISIMBA_FORM']??'')!=='questionworkshop'
                || ($_SERVER['HTTP_SEC_FETCH_SITE']??'')!=='same-origin'){
                http_response_code(403);exit;
            }
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode(['token'=>$this->csrf->issueForSession('questionworkshop_write')]);exit;
        }
        try {
            if(in_array($action,['create','generate','save','import','delete'],true)){
                if(($_SERVER['REQUEST_METHOD']??'')!=='POST'||!$this->csrf->consume('questionworkshop_write',$this->param('csrf_token')))throw new DomainException('expired');
                if($action==='create'){
                    $title=$service->title($title);$extract=$this->getObject('workshopsource');
                    $upload=$_FILES['sourcefile']??[];
                    if(($upload['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_NO_FILE){
                        if($source!=='')throw new DomainException('choose_source');
                        $source=$extract->upload($upload);
                    }else{$source=$extract->validate($source);}
                    $count=$this->param('count','5');
                    if(!preg_match('/^(?:[1-9]|[12][0-9]|30)$/D',$count))throw new DomainException('count_invalid');
                    $id=$store->createSet($owner,$title,$source,[],(int)$count);
                    return $this->nextAction('view',['id'=>$id]);
                }
                $row=$service->read($id);
                if($action==='delete'){
                    if($this->param('confirm_delete')!=='1')throw new DomainException('delete_confirm_required');
                    if((string)$row['version']!==$this->param('version'))throw new DomainException('changed');
                    $store->removeOwned($row['id'],$owner,(int)$row['version']);
                    return $this->nextAction('list');
                }
                if($action==='import'){
                    $context=(string)$this->getObject('dbcontext','context')->getContextCode();
                    if($context!==$this->param('contextcode'))throw new DomainException('course_forbidden');
                    $service->importCourse($id,$context);
                    return $this->nextAction('view',['id'=>$id]);
                }
                if($action==='generate'){
                    if($this->param('consent')!=='1')throw new DomainException('consent_required');
                    $service->generate($id);
                }else{
                    if(empty(json_decode($row['questions_json'],true)))throw new DomainException('questions_invalid');
                    if((string)$row['version']!==$this->param('version'))throw new DomainException('changed');
                    $questions=$service->validate($this->getParam('questions',[]),(int)$row['question_count']);
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
                    $body=$format==='odt'?$export->odt($row,$answers):$export->text($row,$answers);
                    header('X-Content-Type-Options: nosniff');header('Content-Type: '.($format==='odt'?'application/vnd.oasis.opendocument.text':'text/plain; charset=UTF-8'));
                    header('Content-Disposition: attachment; filename="'.($answers?'answer-key':'questions').'.'.$format.'"');echo $body;exit;
                }
            }
        }catch(DomainException $e){
            $error=$e->getMessage();http_response_code($error==='not_found'?404:422);
            if($error==='expired' && $id!==''){
                try{$row=$service->read($id);}catch(DomainException $ignored){}
            }
        }
        catch(Throwable $e){$error='storage_failed';http_response_code(500);}
        $this->setVar('workshopError',$error);$this->setVar('workshopRow',$row);
        $this->setVar('workshopCount',$this->param('count','5'));$this->setVar('workshopTitle',$title);$this->setVar('workshopSource',$source);
        $this->setVar('workshopToken',$this->csrf->issueForSession('questionworkshop_write'));
        $page=max(1,(int)$this->param('page','1'));
        $this->setVar('workshopPage',$page);$this->setVar('workshopSets',$row?[]:$store->owned($owner,$page));
        $this->setVar('workshopDraft', $error!==''&&$action==='save'?$this->getParam('questions',[]):null);
        return 'workspace_tpl.php';
    }
}
