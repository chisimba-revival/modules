<?php
/** Public catalogue and explicit, CSRF-protected registration actions. @author Derek Keats */
if(empty($GLOBALS['kewl_entry_point_run']))die('No direct access');
class webinar extends controller
{
    public function init(){}
    public function requiresLogin($action=null)
    {return !in_array($this->getParam('action','archive'),['archive','view','speakers','register','confirm','unsubscribe','notice'],true);}
    private function param($key){$v=$this->getParam($key,'');return is_string($v)?trim($v):'';}
    private function csrf(){return $this->getObject('nativeauthwebcomposition','security')->build()['csrf'];}
    private function redirectNotice($message)
    {header('Location: '.html_entity_decode($this->uri(['action'=>'notice','message'=>$message],'webinar'),ENT_QUOTES,'UTF-8'),true,303);exit;}
    public function dispatch($action=null)
    {
        $action=$this->param('action')?:'archive';
        if(in_array($action,['register','confirm','unsubscribe'],true))return $this->formAction($action);
        if($action==='notice'){
            $message=$this->param('message');if(!in_array($message,['pending','confirmed','unsubscribed'],true))$message='invalid';
            $this->setVar('webinarNotice',$message);return 'registration_tpl.php';
        }
        $record=$action==='view'?$this->getObject('webinarstore')->one($this->param('id')):null;
        if(!in_array($action,['archive','view','speakers'],true)||($action==='view'&&!$record)){http_response_code(404);$this->setVar('webinarMissing',true);}
        $this->setVar('webinarRecord',$record);$this->setVar('webinarAction',$action);
        return 'archive_tpl.php';
    }
    private function formAction($action)
    {
        header('Cache-Control: no-store');header('Referrer-Policy: no-referrer');
        $this->loadClass('webinarschedule','webinar');
        $token=$this->param('token');$record=null;$error='';
        if($action==='register'){
            $record=$this->getObject('webinarstore')->one($this->param('id'));
            if(!$record||!webinarschedule::canRegister($record))$error='closed';
        }elseif($action==='confirm'){
            $registration=$this->getObject('webinarregistrations')->token($token);
            if(!$registration)$error='expired';
            else $record=$this->getObject('webinarstore')->one($registration['webinar_id']);
        }elseif(!$this->getObject('audienceservice','audience')->contactForToken($token))$error='expired';
        if(strtoupper($_SERVER['REQUEST_METHOD']??'GET')==='POST' && $error===''){
            if(!$this->csrf()->consume('webinar-'.$action,$this->param('csrf_token')))$error='session_expired';
            else try {
                $service=$this->getObject('webinarregistrationservice');
                if($action==='register'){
                    if($this->param('website')!=='')throw new DomainException('invalid');
                    $service->register($record,$this->param('name'),$this->param('email'),$this->param('consent')==='1',$_SERVER['REMOTE_ADDR']??'unknown');
                    $this->redirectNotice('pending');
                }elseif($action==='confirm'){$service->confirm($token);$this->redirectNotice('confirmed');}
                else {
                    if(!$this->getObject('audienceservice','audience')->unsubscribe($token))throw new DomainException('expired');
                    $this->redirectNotice('unsubscribed');
                }
            }catch(DomainException $e){$error=in_array($e->getMessage(),['invalid','closed','expired','try_later'],true)?$e->getMessage():'invalid';}
            catch(Throwable $e){$error='unavailable';}
        }
        $this->setVar('webinarFormAction',$action);$this->setVar('webinarRecord',$record);
        $this->setVar('webinarToken',$token);$this->setVar('webinarError',$error);
        $this->setVar('webinarCsrf',$this->csrf()->issue('webinar-'.$action));
        $this->setVar('webinarInput',['name'=>$this->param('name'),'email'=>$this->param('email'),'consent'=>$this->param('consent')]);
        return 'registration_tpl.php';
    }
}
