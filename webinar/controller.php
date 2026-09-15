<?php
/** Public catalogue and explicit, CSRF-protected registration actions. @author Derek Keats */
if(empty($GLOBALS['kewl_entry_point_run']))die('No direct access');
class webinar extends controller
{
    public function init(){}
    public function requiresLogin($action=null)
    {return !in_array($this->getParam('action','upcoming'),['upcoming','archive','recordings','view','speakers','register','confirm','unsubscribe','notice'],true);}
    private function param($key){$v=$this->getParam($key,'');return is_string($v)?trim($v):'';}
    private function csrf(){return $this->getObject('nativeauthwebcomposition','security')->build()['csrf'];}
    private function redirectNotice($message)
    {header('Location: '.html_entity_decode($this->uri(['action'=>'notice','message'=>$message],'webinar'),ENT_QUOTES,'UTF-8'),true,303);exit;}
    /** Return to the booking section with a one-use, session-owned result. */
    private function redirectBooking($message,$id)
    {
        $_SESSION['webinar_booking_notice'][$id]=$message;
        header('Location: '.html_entity_decode($this->uri(['action'=>'view','id'=>$id],'webinar'),ENT_QUOTES,'UTF-8').'#booking',true,303);exit;
    }
    public function dispatch($action=null)
    {
        if($this->param('action')===''&&($_SERVER['REQUEST_METHOD']??'GET')==='GET')return $this->nextAction('upcoming');
        $action=$this->param('action')?:'upcoming';
        if(in_array($action,['register','confirm','unsubscribe'],true))return $this->formAction($action);
        if($action==='recordings')return $this->recordings();
        if($action==='notice'){
            $message=$this->param('message');if(!in_array($message,['pending','confirmed','unsubscribed'],true))$message='invalid';
            $this->setVar('webinarNotice',$message);return 'registration_tpl.php';
        }
        $record=$action==='view'?$this->getObject('webinarstore')->one($this->param('id')):null;
        if(!in_array($action,['upcoming','archive','recordings','view','speakers'],true)||($action==='view'&&!$record)){http_response_code(404);$this->setVar('webinarMissing',true);}
        if($record&&$record['kind']==='webinar'){
            $this->loadClass('webinarschedule','webinar');$start=webinarschedule::start($record);
            if(($start&&$start->getTimestamp()>time())||isset($_SESSION['webinar_booking_notice'][$record['id']]))return $this->formAction('register');
        }
        $this->setVar('webinarRecord',$record);$this->setVar('webinarAction',$action);
        return 'archive_tpl.php';
    }
    /** JSON is a public read-only enhancement of the same paginated page. */
    private function recordings()
    {
        $store=$this->getObject('webinarvideos','webinar');$rows=$store->catalogue();
        try{$page=webinarvideos::page($this->getParam('page',1),count($rows));}
        catch(InvalidArgumentException $error){http_response_code(400);$this->setVar('webinarMissing',true);return 'archive_tpl.php';}
        $slice=array_slice($rows,($page-1)*6,6);$r=$this->getObject('webinarrenderer','webinar');
        $data=['html'=>$this->getObject('videocardrenderer','contentblocks')->cards($slice,'webinar-video-player',$r->text('watch')),
            'count'=>count($slice),'total'=>count($rows),'channel'=>$store->channel(),
            'next'=>$page*6<count($rows)?html_entity_decode($this->uri(['action'=>'recordings','page'=>$page+1],'webinar'),ENT_QUOTES,'UTF-8'):null];
        if($this->param('fragment')==='1'){header('Content-Type: application/json; charset=UTF-8');header('X-Content-Type-Options: nosniff');echo json_encode($data,JSON_THROW_ON_ERROR);exit;}
        $this->setVar('videoGallery',$data);return 'recordings_tpl.php';
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
                    $this->redirectBooking('pending',$record['id']);
                }elseif($action==='confirm'){$confirmed=$service->confirm($token);$this->redirectBooking('confirmed',$confirmed['id']);}
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
        if($action==='register'&&$record){
            if(($_SERVER['REQUEST_METHOD']??'GET')==='GET'&&$this->param('action')==='register'){header('Location: '.html_entity_decode($this->uri(['action'=>'view','id'=>$record['id']],'webinar'),ENT_QUOTES,'UTF-8').'#booking',true,303);exit;}
            $notice=$_SESSION['webinar_booking_notice'][$record['id']]??'';unset($_SESSION['webinar_booking_notice'][$record['id']]);
            $this->setVar('webinarBookingNotice',in_array($notice,['pending','confirmed'],true)?$notice:'');
            $this->setVar('webinarInlineBooking',true);$this->setVar('webinarAction','view');return 'archive_tpl.php';
        }
        return 'registration_tpl.php';
    }
}
