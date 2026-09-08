<?php
/** Certificate management and course-certificate delivery boundary. */
if (empty($GLOBALS['kewl_entry_point_run'])) { die(); }
class certificate_service extends controller
{
    private const CSRF='certificate_service_manage';
    public function init(){ $this->service=$this->getObject('certificateservice','certificate-service');$this->user=$this->getObject('user','security');$this->users=$this->getObject('userservice','security');$this->context=$this->getObject('dbcontext','context');$this->language=$this->getObject('language','language');$this->settings=$this->getObject('dbsysconfig','sysconfig');$this->groups=$this->getObject('groupservice','groupadmin');$this->registrations=$this->getObject('registrationservice','registration-service');$stack=$this->getObject('nativeauthwebcomposition','security')->build();$this->csrf=$stack['csrf']; }
    public function dispatch($action)
    {
        $contextCode=$this->context->getContextCode();
        switch((string)$action){
            case 'savebase': return $this->saveBase($contextCode);
            case 'savesigner': return $this->saveSigner($contextCode);
            case 'deletebase': return $this->deleteBase($contextCode);
            case 'deletesigner': return $this->deleteSigner($contextCode);
            case 'previewbase': return $this->previewBase();
            case 'assigncourse': return $this->assignCourse($contextCode);
            case 'downloadcourse': return $this->downloadCourse($contextCode);
            case 'audit': return $this->audit();
            case 'saveauditidentity': return $this->saveAuditIdentity();
            case 'downloadaudit': return $this->downloadAudit();
            default: return $this->manage($contextCode);
        }
    }
    private function audit($message='',$error='')
    {
        if(!$this->mayAudit()){return $this->nextAction(null,array('error'=>'noaccess'),'_default');}
        if($message===''){$message=$this->param('message');}if($error===''){$error=$this->param('error');}
        $certificate=$this->param('certificate_number');$identity=$this->param('identity_document_number');
        $results=($certificate!==''||$identity!=='')?$this->service->auditSearch($certificate,$identity):array();
        if($certificate!==''||$identity!==''){$audit=$this->service->recordAuditSearch($this->user->userId(),count($results));if(empty($audit['ok'])){$results=array();$error='auditunavailable';}}
        $this->setVar('certificateAuditResults',$results);$this->setVar('certificateAuditNumber',$certificate);$this->setVar('certificateAuditIdentity',$identity);
        $this->setVar('certificateAuditCsrf',$this->csrf->issue(self::CSRF));$this->setVar('certificateAuditIsAdmin',$this->user->isAdmin());
        $this->setVar('certificateAuditMessage',$message);$this->setVar('certificateAuditError',$error);return 'audit_tpl.php';
    }
    private function saveAuditIdentity()
    {
        if(!$this->user->isAdmin()||!$this->validPost()){return $this->nextAction('audit',array('error'=>'invalid'));}
        $account=$this->param('account');$target=strpos($account,'@')!==false?$this->users->findByEmail($account):$this->users->findByUsername($account);
        if(!is_array($target)||empty($target['userid'])){return $this->nextAction('audit',array('error'=>'accountnotfound'));}
        $result=$this->registrations->saveIdentityForUser($target['userid'],$this->param('identity_document_type'),$this->param('identity_document_number'),$this->user->userId());
        return empty($result['ok'])?$this->nextAction('audit',array('error'=>(string)($result['code']??'invalid'))):$this->nextAction('audit',array('message'=>'identitysaved'));
    }
    private function downloadAudit()
    {
        if(!$this->mayAudit()){return $this->nextAction(null,array('error'=>'noaccess'),'_default');}
        $issuance=$this->service->issuanceById($this->param('id'));
        if(!is_array($issuance)){return $this->nextAction('audit',array('error'=>'notfound'));}
        $audit=$this->service->recordAuditSearch($this->user->userId(),1);
        if(empty($audit['ok'])){return $this->nextAction('audit',array('error'=>'auditunavailable'));}
        $pdf=$this->getObject('certificatepdfrenderer','certificate-service')->render($issuance);
        $filename='certificate-'.preg_replace('/[^A-Z0-9-]+/','-',strtoupper((string)$issuance['certificate_number'])).'.pdf';
        header('Content-Type: application/pdf');header('Content-Disposition: inline; filename="'.$filename.'"');header('Content-Length: '.strlen($pdf));header('Cache-Control: private, no-store');echo $pdf;exit;
    }
    private function manage($contextCode)
    {
        if(!$this->user->isAdmin()&&!$this->mayManageCourse($contextCode)){return $this->nextAction(null,array('error'=>'noaccess'),'_default');}
        $this->setVar('certificateBases',$this->service->activeBases());$this->setVar('certificateSigners',$this->service->activeSigners());$this->setVar('certificateAssignment',$contextCode===''?false:$this->service->assignmentFor('course',$contextCode));$this->setVar('certificateContextCode',$contextCode);$this->setVar('certificateCsrf',$this->csrf->issue(self::CSRF));$this->setVar('certificateIsAdmin',$this->user->isAdmin());return 'manage_tpl.php';
    }
    private function saveBase($contextCode)
    {
        if(!$this->user->isAdmin()||!$this->validPost()){return $this->saveResponse(array('ok'=>false,'code'=>'invalid'),$contextCode);}
        $input=array('name'=>$this->param('name'),'organisation'=>$this->param('organisation'),'companyName'=>$this->param('company_name'),'companyLocation'=>$this->param('company_location'),'websiteUrl'=>$this->param('website_url'),'primaryColour'=>$this->param('primary_colour'),'accentColour'=>$this->param('accent_colour'));
        $id=$this->param('id');$result=$id===''?$this->service->createBase($input,$this->user->userId()):$this->service->updateBase($id,$input);
        if(!empty($result['ok'])&&!empty($_FILES['logo']['name'])){$result['ok']=$this->service->storeImageAsset($_FILES['logo'],'logo',$result['id']);}
        return $this->saveResponse($result,$contextCode);
    }
    private function saveSigner($contextCode)
    {
        if(!$this->user->isAdmin()||!$this->validPost()){return $this->saveResponse(array('ok'=>false,'code'=>'invalid'),$contextCode);}
        $input=array('name'=>$this->param('name'),'title'=>$this->param('title'));
        $id=$this->param('id');$result=$id===''?$this->service->createSigner($input,$this->user->userId()):$this->service->updateSigner($id,$input);
        if(!empty($result['ok'])&&!empty($_FILES['signature']['name'])){$result['ok']=$this->service->storeImageAsset($_FILES['signature'],'signature',$result['id']);}
        return $this->saveResponse($result,$contextCode);
    }
    private function deleteBase($contextCode)
    {
        if(!$this->user->isAdmin()||!$this->validPost()){return $this->saveResponse(array('ok'=>false,'code'=>'invalid'),$contextCode);}
        return $this->saveResponse($this->service->archiveBase($this->param('id')),$contextCode);
    }
    private function deleteSigner($contextCode)
    {
        if(!$this->user->isAdmin()||!$this->validPost()){return $this->saveResponse(array('ok'=>false,'code'=>'invalid'),$contextCode);}
        return $this->saveResponse($this->service->archiveSigner($this->param('id')),$contextCode);
    }
    private function previewBase()
    {
        if(!$this->user->isAdmin()){return $this->nextAction(null,array('error'=>'noaccess'),'_default');}
        $base=$this->service->baseById($this->param('id'));if(!$base){return $this->nextAction(null,array('error'=>'invalid'));}
        $signers=$this->service->activeSigners();$signer=!empty($signers)?$signers[0]:array('name'=>'Sample Signer','title'=>'Authorised Signer','signature_path'=>null);
        $issuance=array('certificate_number'=>'SAMPLE-CERTIFICATE','snapshot'=>array('recipient_name'=>'Student Full Name','resource_title'=>'Sample Course Title','completed_at'=>date('Y-m-d H:i:s'),'base'=>$base,'signer'=>$signer));
        $pdf=$this->getObject('certificatepdfrenderer','certificate-service')->render($issuance);header('Content-Type: application/pdf');header('Content-Disposition: inline; filename="certificate-design-sample.pdf"');header('Content-Length: '.strlen($pdf));header('Cache-Control: private, no-store');echo $pdf;exit;
    }
    private function assignCourse($contextCode){if(!$this->mayManageCourse($contextCode)||!$this->validPost()){return $this->saveResponse(array('ok'=>false,'code'=>'invalid'),$contextCode);}$result=$this->service->assign('course',$contextCode,$this->param('base_id'),$this->param('signer_id'),$this->user->userId());return $this->saveResponse($result,$contextCode,'assigned');}
    private function saveResponse(array $result,$contextCode,$successMessage='saved')
    {
        if($this->param('ajax')==='1'){
            header('Content-Type: application/json; charset=UTF-8');
            $message=!empty($result['ok'])?$successMessage:(!empty($result['code'])&&$result['code']==='in_use'?'inuse':'invalid');
            echo json_encode(array('ok'=>!empty($result['ok']),'message'=>$message,'csrfToken'=>$this->csrf->issue(self::CSRF),'bases'=>$this->service->activeBases(),'signers'=>$this->service->activeSigners(),'assignment'=>$contextCode===''?false:$this->service->assignmentFor('course',$contextCode)),JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);exit;
        }
        $message=!empty($result['ok'])?$successMessage:(!empty($result['code'])&&$result['code']==='in_use'?'inuse':'invalid');
        return $this->nextAction(null,array(!empty($result['ok'])?'message':'error'=>$message));
    }
    private function downloadCourse($contextCode)
    {
        if($contextCode===''||!$this->user->isLoggedIn()){return $this->nextAction(null,array('error'=>'notconfigured'));}
        $eligibility=$this->getObject('coursecompletioneligibilityservice','contextcontent')->evaluate($contextCode,$this->user->userId());
        if(empty($eligibility['eligible'])){return $this->nextAction(null,array('error'=>'noteligible'));}
        $result=$this->service->issue(array('resourceType'=>'course','resourceId'=>$contextCode,'userId'=>$this->user->userId(),'recipientName'=>$this->user->fullname(),'resourceTitle'=>$this->context->getTitle($contextCode),'completionReference'=>$eligibility['reference'],'completedAt'=>$eligibility['completed_at'],'eligible'=>true));
        if(empty($result['ok'])){
            if(($result['code']??'')==='identity_required'){return $this->nextAction('identity',array(),'registration-service');}
            return $this->nextAction(null,array('error'=>'notconfigured'));
        }
        $pdf=$this->getObject('certificatepdfrenderer','certificate-service')->render($result['issuance']);$filename='certificate-'.preg_replace('/[^a-z0-9-]+/i','-',$contextCode).'.pdf';header('Content-Type: application/pdf');header('Content-Disposition: attachment; filename="'.$filename.'"');header('Content-Length: '.strlen($pdf));header('Cache-Control: private, no-store');echo $pdf;exit;
    }
    private function mayManageCourse($contextCode){return $contextCode!==''&&($this->user->isAdmin()||$this->user->isContextLecturer($this->user->userId(),$contextCode));}
    private function mayAudit(){if($this->user->isAdmin())return true;$name=trim((string)$this->settings->getValue('CERTIFICATE_AUDIT_ASSISTANT_GROUP','certificate-service','certificate_audit_assistants'));$group=$this->groups->groupIdForName($name);return $group!==false&&$this->groups->isGroupMember($this->user->userId(),$group);}
    private function validPost(){return strtoupper((string)($_SERVER['REQUEST_METHOD']??''))==='POST'&&$this->csrf->consume(self::CSRF,$this->param('csrf_token'));}
    private function param($name){$value=$this->getParam($name,'');return is_scalar($value)?trim((string)$value):'';}
}
class_alias('certificate_service','certificate-service');
?>
