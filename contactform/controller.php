<?php
if(empty($GLOBALS['kewl_entry_point_run']))die('No direct access');
class contactform extends controller
{
    private $service;private $csrf;
    public function init(){$this->service=$this->getObject('contactservice','contactform');$this->csrf=$this->getObject('nativeauthwebcomposition','security')->build()['csrf'];$this->setLayoutTemplate('layout_tpl.php');}
    public function requiresLogin($action){return in_array($action,['manage','moderate','review'],true);}
    private function param($key,$default=''){$value=$this->getParam($key,$default);return is_string($value)?$value:$default;}
    private function text($key){return $this->getObject('language','language')->code2Txt('mod_contactform_'.$key,'contactform');}
    private function json($data,$code=200){http_response_code($code);header('Content-Type: application/json; charset=UTF-8');echo json_encode($data,JSON_THROW_ON_ERROR);exit;}
    public function dispatch($action)
    {
        header('Cache-Control: private, no-store');$action=$action?:'view';
        if($action==='token')$this->json(['token'=>$this->csrf->issue('contactform_submit')]);
        if(in_array($action,['moderate','review'],true)){
            if(!$this->service->canManage()){http_response_code(403);$this->setVar('contactMessage',$this->text('forbidden'));return 'message_tpl.php';}
            if(($_SERVER['REQUEST_METHOD']??'')!=='POST'||!$this->csrf->consume('contactform_manage',$this->param('csrf_token'))){http_response_code(403);$this->setVar('contactMessage',$this->text('expired_manage'));return 'message_tpl.php';}
            try{
                if($action==='review')$this->service->reviewPage(max(1,(int)$this->param('page','1')));
                else { $ids=$this->getParam('ids',[]);if(!is_array($ids))throw new DomainException('invalid');$this->service->moderate($ids,$this->param('operation')); }
                return $this->nextAction('manage',['folder'=>$this->param('folder','inbox'),'changed'=>'1']);
            }catch(DomainException $e){http_response_code(400);$this->setVar('contactMessage',$this->text('invalid_selection'));return 'message_tpl.php';}
        }
        if($action==='manage'){
            if(!$this->service->canManage()){http_response_code(403);$this->setVar('contactMessage',$this->text('forbidden'));return 'message_tpl.php';}
            $page=max(1,min(10000,(int)$this->param('page','1')));$folder=$this->param('folder','inbox');if(!in_array($folder,['inbox','spam','trash'],true))$folder='inbox';$rows=$this->service->recent($page,$folder);
            $this->setVar('contactFolder',$folder);$this->setVar('contactChanged',$this->param('changed')==='1');$this->setVar('contactManageToken',$this->csrf->issue('contactform_manage'));
            $this->setVar('contactPage',$page);$this->setVar('contactMore',count($rows)>20);$this->setVar('contactRows',array_slice($rows,0,20));return 'manage_tpl.php';
        }
        if($action==='thanks'){
            $key=$this->getSession('contactform_receipt','');
            if(!in_array($key,['preview_received','received','delivery_pending'],true))return $this->form();
            $this->setVar('contactMessage',$this->text($key));return 'message_tpl.php';
        }
        if($action==='submit'){
            $in=[];foreach(['id','name','email','subject','message','website','abuse_issued_at','abuse_nonce','abuse_signature'] as $key)$in[$key]=$this->param($key);
            $error='';$code=400;
            if(($_SERVER['REQUEST_METHOD']??'')!=='POST'||!$this->csrf->consume('contactform_submit',$this->param('csrf_token'))){$error='expired';$code=403;}
            else try{
                $status=$this->service->submit($in,$_SERVER['REMOTE_ADDR']??'unknown');
                $key=$status==='preview'?'preview_received':($status==='sent'?'received':'delivery_pending');$this->setSession('contactform_receipt',$key);
                if(str_contains($_SERVER['HTTP_ACCEPT']??'','application/json'))$this->json(['ok'=>true,'message'=>$this->text($key),'url'=>html_entity_decode($this->uri(['action'=>'thanks'],'contactform'),ENT_QUOTES,'UTF-8')]);
                return $this->nextAction('thanks');
            }catch(DomainException $e){$error=in_array($e->getMessage(),['invalid','unavailable','limited','conflict','protection'],true)?$e->getMessage():'failed';$code=$error==='limited'?429:($error==='unavailable'?503:400);}
            catch(Throwable $e){$error='failed';$code=500;}
            if(str_contains($_SERVER['HTTP_ACCEPT']??'','application/json'))$this->json(['ok'=>false,'message'=>$this->text($error)],$code);
            http_response_code($code);return $this->form($in,$error);
        }
        if($action!=='view'){http_response_code(404);$this->setVar('contactMessage',$this->text('unavailable'));return 'message_tpl.php';}
        return $this->form();
    }
    private function form($input=[],$error='')
    {
        $this->setVar('contactEvidence',$this->getObject('contactguard','contactform')->evidence());
        $this->setVar('contactInput',$input+['id'=>bin2hex(random_bytes(16)),'name'=>'','email'=>'','subject'=>'','message'=>'','website'=>'']);
        $this->setVar('contactError',$error===''?'':$this->text($error));$this->setVar('contactToken',$this->csrf->issue('contactform_submit'));return 'form_tpl.php';
    }
}
