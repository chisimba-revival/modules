<?php
/** KengaLearn course spoken-assessment journeys. @author Derek Keats */
if (empty($GLOBALS['kewl_entry_point_run'])) die('No direct access');
class spokenassessment extends controller
{
    private $csrf;
    public function init() { $this->csrf=$this->getObject('nativeauthwebcomposition','security')->build()['csrf']; }
    public function requiresLogin($action = null) { return true; }
    private function param($key, $default = '') { $value=$this->getParam($key,$default); return is_string($value)?trim($value):''; }
    public function dispatch($action = null)
    {
        header('Cache-Control: private, no-store');
        $user=(string)$this->getObject('user','security')->userId();
        $context=(string)$this->getObject('dbcontext','context')->getContextCode();
        $policy=$this->getObject('spokenpolicy'); $service=$this->getObject('spokenservice');
        $action=$this->param('action','list'); $id=$this->param('id');
        $this->setVar('spokenError',''); $this->setVar('spokenInput',[]);
        $this->setVar('spokenUser',$user); $this->setVar('spokenContext',$context);
        $this->setVar('spokenTeacher',$policy->teacher($user,$context));
        $this->setVar('spokenAction',$action); $this->setVar('spokenActivity',null); $this->setVar('spokenAttempt',null);
        if (!$policy->member($user,$context)) return $this->error('course_required',403);
        try {
            $mutations=['save','upload','approve','feedback','reflect','retry'];
            if (in_array($action,$mutations,true)) {
                if (($_SERVER['REQUEST_METHOD']??'')!=='POST' || $this->param('contextcode')!==$context || !$this->csrf->consume('spokenassessment_write',$this->param('csrf_token'))) return $this->error('expired',403);
                if ($action==='save') {
                    $input=[]; foreach (['title','prompt','outcomes','rubric_id','published','version'] as $key) $input[$key]=$this->param($key);
                    $this->setVar('spokenInput',$input);
                    $id=$service->saveActivity($id,$user,$context,$input);
                    return $this->nextAction('activity',['id'=>$id]);
                }
                if ($action==='upload') {
                    $id=$service->upload($id,$user,$context,is_array($_FILES['audio']??null)?$_FILES['audio']:[],$this->param('consent'),$this->param('upload_token'));
                    if ($this->jsonUpload()) $this->uploadResult(['ok'=>true,'redirect'=>$this->uri(['action'=>'attempt','id'=>$id],'spokenassessment')],200);
                    return $this->nextAction('attempt',['id'=>$id]);
                }
                if ($action==='approve') $service->approve($id,$user,$context,$this->param('transcript'));
                if ($action==='feedback') $service->feedback($id,$user,$context,$this->param('feedback'));
                if ($action==='reflect') $service->reflect($id,$user,$context,$this->param('reflection'));
                if ($action==='retry') $service->retry($id,$user,$context);
                return $this->nextAction('attempt',['id'=>$id]);
            }
            if (in_array($action,['attempt','audio'],true)) {
                $attempt=$service->attempt($id,$user,$context); $this->setVar('spokenAttempt',$attempt);
                if ($action==='audio') $this->getObject('spokenfiles')->deliver($attempt);
                $this->setVar('spokenActivity',$this->getObject('spokenstore')->activity($attempt['activity_id']));
            } elseif ($action==='activity' || ($action==='edit' && $id!=='')) {
                $this->setVar('spokenActivity',$service->activity($id,$user,$context));
            } elseif (!in_array($action,['list','edit'],true)) return $this->error('unavailable',404);
            if ($action==='edit' && !$policy->teacher($user,$context)) return $this->error('unavailable',404);
        } catch (DomainException $e) { return $this->error($e->getMessage(),$e->getMessage()==='unavailable'?404:400); }
        catch (Throwable $e) { return $this->error('service_unavailable',503); }
        $this->setVar('spokenToken',$this->csrf->issue('spokenassessment_write'));
        $this->setVar('spokenPage',max(1,min(10000,(int)$this->param('page','1'))));
        return 'workspace_tpl.php';
    }
    private function jsonUpload()
    { return $this->param('action')==='upload' && str_contains($_SERVER['HTTP_ACCEPT']??'','application/json'); }
    private function uploadResult(array $data, $status)
    { http_response_code($status); header('Content-Type: application/json; charset=UTF-8'); echo json_encode($data,JSON_HEX_TAG|JSON_HEX_AMP); exit; }
    private function error($key, $status)
    {
        if ($this->jsonUpload()) $this->uploadResult(['ok'=>false,'message'=>$this->getObject('spokenrenderer')->text($key),'token'=>$this->csrf->issue('spokenassessment_write')],$status);
        $input=[];
        foreach (['title','prompt','outcomes','transcript','feedback','reflection'] as $field) if ($this->param($field)!=='') $input[$field]=mb_substr($this->param($field),0,30000);
        $this->setVar('spokenInput',$input); http_response_code($status); $this->setVar('spokenError',$key); return 'message_tpl.php';
    }
}
