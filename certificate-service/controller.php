<?php
/** Certificate management and course-certificate delivery boundary. */
if (empty($GLOBALS['kewl_entry_point_run'])) { die(); }
class certificate_service extends controller
{
    private const CSRF='certificate_service_manage';
    public function init(){ $this->service=$this->getObject('certificateservice','certificate-service');$this->user=$this->getObject('user','security');$this->context=$this->getObject('dbcontext','context');$this->language=$this->getObject('language','language');$stack=$this->getObject('nativeauthwebcomposition','security')->build();$this->csrf=$stack['csrf']; }
    public function dispatch($action)
    {
        $contextCode=$this->context->getContextCode();
        switch((string)$action){
            case 'savebase': return $this->saveBase($contextCode);
            case 'savesigner': return $this->saveSigner($contextCode);
            case 'assigncourse': return $this->assignCourse($contextCode);
            case 'downloadcourse': return $this->downloadCourse($contextCode);
            default: return $this->manage($contextCode);
        }
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
    private function assignCourse($contextCode){if(!$this->mayManageCourse($contextCode)||!$this->validPost()){return $this->saveResponse(array('ok'=>false,'code'=>'invalid'),$contextCode);}$result=$this->service->assign('course',$contextCode,$this->param('base_id'),$this->param('signer_id'),$this->user->userId());return $this->saveResponse($result,$contextCode,'assigned');}
    private function saveResponse(array $result,$contextCode,$successMessage='saved')
    {
        if($this->param('ajax')==='1'){
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode(array('ok'=>!empty($result['ok']),'message'=>!empty($result['ok'])?$successMessage:'invalid','csrfToken'=>$this->csrf->issue(self::CSRF),'bases'=>$this->service->activeBases(),'signers'=>$this->service->activeSigners(),'assignment'=>$contextCode===''?false:$this->service->assignmentFor('course',$contextCode)),JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);exit;
        }
        return $this->nextAction(null,array(!empty($result['ok'])?'message':'error'=>!empty($result['ok'])?$successMessage:'invalid'));
    }
    private function downloadCourse($contextCode)
    {
        if($contextCode===''||!$this->user->isLoggedIn()){return $this->nextAction(null,array('error'=>'notconfigured'));}
        $eligibility=$this->getObject('coursecompletioneligibilityservice','contextcontent')->evaluate($contextCode,$this->user->userId());
        if(empty($eligibility['eligible'])){return $this->nextAction(null,array('error'=>'noteligible'));}
        $result=$this->service->issue(array('resourceType'=>'course','resourceId'=>$contextCode,'userId'=>$this->user->userId(),'recipientName'=>$this->user->fullname(),'resourceTitle'=>$this->context->getTitle($contextCode),'completionReference'=>$eligibility['reference'],'completedAt'=>$eligibility['completed_at'],'eligible'=>true));
        if(empty($result['ok'])){return $this->nextAction(null,array('error'=>'notconfigured'));}
        $pdf=$this->getObject('certificatepdfrenderer','certificate-service')->render($result['issuance']);$filename='certificate-'.preg_replace('/[^a-z0-9-]+/i','-',$contextCode).'.pdf';header('Content-Type: application/pdf');header('Content-Disposition: attachment; filename="'.$filename.'"');header('Content-Length: '.strlen($pdf));header('Cache-Control: private, no-store');echo $pdf;exit;
    }
    private function mayManageCourse($contextCode){return $contextCode!==''&&($this->user->isAdmin()||$this->user->isContextLecturer($this->user->userId(),$contextCode));}
    private function validPost(){return strtoupper((string)($_SERVER['REQUEST_METHOD']??''))==='POST'&&$this->csrf->consume(self::CSRF,$this->param('csrf_token'));}
    private function param($name){$value=$this->getParam($name,'');return is_scalar($value)?trim((string)$value):'';}
}
class_alias('certificate_service','certificate-service');
?>
