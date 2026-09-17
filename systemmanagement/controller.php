<?php
/** Administration controller for operational notices and maintenance mode. @author Derek Keats @package systemmanagement */
if (empty($GLOBALS['kewl_entry_point_run'])) die('You cannot view this page directly');
class systemmanagement extends controller
{
    private $pendingEmailDraft=null;
    private const CSRF='systemmanagement_mutation';
    private $user; private $config; private $notices; private $service; private $mailer; private $clock; private $csrf;
    /** Initialise the operational services. */
    public function init(){$this->user=$this->getObject('user','security');$this->config=$this->getObject('dbsysconfig','sysconfig');$this->notices=$this->getObject('dbsystemnotices');$this->service=$this->getObject('systemmanagementservice');$this->mailer=$this->getObject('systemmanagementmailer');$this->clock=$this->getObject('timeanddateservice','timeanddate-service');$this->csrf=$this->getObject('nativeauthwebcomposition','security')->build()['csrf'];$this->appendArrayVar('headerParams','<link rel="stylesheet" href="'.$this->getResourceUri('systemmanagement.css').'"/>');$this->appendArrayVar('headerParams','<script defer src="'.$this->getResourceUri('systemmanagement.js').'"></script>');}
    /** Require authentication for the console but permit the public offline page. */
    public function requiresLogin($action){return (string)$action!=='offline';}
    /** Route administration mutations and the offline display. */
    public function dispatch($action){$action=(string)$action;if($action==='offline')return $this->offline();if(!$this->user->isAdmin())return $this->wantsJson()?$this->jsonResponse(array('ok'=>false,'error'=>$this->emailText('ajax_access')),403):'noaccess_tpl.php';if($action==='saveemaildraft')return $this->saveEmailDraft();if($action==='save')return $this->save();if($action==='setmaintenance')return $this->setMaintenance();if($action==='sendmaintenanceemail')return $this->sendMaintenanceEmail();if($action==='deletenotice')return $this->deleteNotice();return $this->index();}
    /** Render the administrator's operational console. */
    private function index($message='',$error='',?array $emailDraft=null)
    {
        if($message===''){
            $changed=$this->param('maintenancechanged');
            if($changed==='offline')$message=$this->emailText('ajax_offline');
            elseif($changed==='online')$message=$this->emailText('ajax_online');
        }
        // The framework may already have read maintenance state earlier in this request.
        $this->config->setProperties('systemmanagement');
        $maintenance=$this->service->maintenance();
        $saved=$this->emailDraft();
        $draft=$emailDraft??$this->pendingEmailDraft??$saved['content']??array('audience'=>'everyone','subject'=>$this->emailText('email_subject'),'message'=>$maintenance['message']);
        $state=$this->pendingEmailDraft!==null&&$this->pendingEmailDraft!==($saved['content']??null)?'draft':($saved['state']??'draft');
        $token=$this->csrf->issue(self::CSRF);
        if($this->wantsJson()){
            $active=(bool)$maintenance['active'];
            return $this->jsonResponse(array('ok'=>$error==='','message'=>$message,'error'=>$error,'csrf'=>$token,
                'draftState'=>$state,'draftLabel'=>$this->emailText('draft_state_'.$state),
                'maintenance'=>array('active'=>$active,'status'=>$this->emailText($active?'ajax_status_offline':'ajax_status_online'),
                    'nextState'=>$active?'online':'offline','buttonLabel'=>$this->emailText($active?'ajax_bring_online':'ajax_take_offline'),
                    'icon'=>$this->getObject('iconservice','ui')->render($active?'power-off':'power',array('decorative'=>true))),
                'deliveries'=>$this->mailer->recentDeliveries()));
        }
        $this->setVar('systemMaintenance',$maintenance);$this->setVar('systemEmailDraft',$draft);
        $this->setVar('systemEmailDraftState',$state);$this->setVar('systemAudienceCounts',$this->mailer->audienceCounts());
        $this->setVar('systemEmailReadiness',$this->mailer->readiness());$this->setVar('systemEmailDeliveries',$this->mailer->recentDeliveries());
        $this->setVar('systemCsrf',$token);$this->setVar('systemMessage',$message);$this->setVar('systemError',$error);
        $this->setVar('systemService',$this->service);$this->setVar('systemClock',$this->clock);return 'dashboard_tpl.php';
    }
    private function wantsJson(){return $this->param('response')==='json';}
    /** Structured responses never include draft text and are not cacheable. */
    protected function jsonResponse(array $payload,$status=200)
    {
        http_response_code($status);header('Content-Type: application/json; charset=utf-8');header('Cache-Control: no-store');
        echo json_encode($payload,JSON_THROW_ON_ERROR|JSON_INVALID_UTF8_SUBSTITUTE);exit;
    }
    /** Save either the maintenance plan or a new notice. */
    private function save(){if(!$this->validPost())return $this->index('','Your session expired. Please try again.');$kind=$this->param('kind');if($kind==='maintenance'){$start=$this->service->localToStorage($this->param('start'));$end=$this->service->localToStorage($this->param('end'));if(($this->param('start')!==''&&$start===null)||($this->param('end')!==''&&$end===null))return $this->index('','Enter valid maintenance dates.');if($start&&$end&&$end<=$start)return $this->index('','Maintenance must end after it starts.');foreach(array('MAINTENANCE_MESSAGE'=>mb_substr($this->param('message'),0,2000),'MAINTENANCE_START_AT'=>$start?:'','MAINTENANCE_END_AT'=>$end?:'') as $name=>$value)$this->config->changeParam($name,'systemmanagement',$value);return $this->index($this->emailText('ajax_plan_saved'));}if($kind==='notice'){$title=mb_substr($this->param('title'),0,255);$body=mb_substr($this->param('notice_message'),0,10000);$audience=$this->param('audience');$start=$this->service->localToStorage($this->param('notice_start'));$end=$this->service->localToStorage($this->param('notice_end'));if($title===''||$body===''||!in_array($audience,array('everyone','admins','lecturers','students'),true))return $this->index('','A title, message and valid audience are required.');if($start&&$end&&$end<=$start)return $this->index('','A notice must end after it starts.');$now=$this->clock->nowStorage();$this->notices->createNotice(array('id'=>bin2hex(random_bytes(16)),'title'=>$title,'message'=>$body,'audience'=>$audience,'starts_at'=>$start,'ends_at'=>$end,'created_by'=>$this->user->userId(),'datecreated'=>$now,'datemodified'=>$now));return $this->index('Notice scheduled.');}return $this->index('','Unknown operation.');}
    /** Manually take the public site offline or return it to service. */
    private function setMaintenance(){if(!$this->validPost())return $this->index('','Your session expired. Please try again.');$offline=$this->param('state')==='offline';$this->config->changeParam('MAINTENANCE_ENABLED','systemmanagement',$offline?'1':'0');if($this->wantsJson())return $this->index($this->emailText($offline?'ajax_offline':'ajax_online'));$location=html_entity_decode($this->uri(array('maintenancechanged'=>$offline?'offline':'online'),'systemmanagement'),ENT_QUOTES,'UTF-8');header('Location: '.$location,true,303);exit;}
    /** Queue planned or unplanned maintenance mail without losing the submitted draft. */
    private function sendMaintenanceEmail()
    {
        // Keep the original text for redisplay, including whitespace and line breaks.
        $draft=array();
        foreach(array('audience'=>'audience','subject'=>'subject','message'=>'email_message') as $field=>$parameter){
            $value=$this->getParam($parameter,'');
            $draft[$field]=is_scalar($value)?(string)$value:'';
        }
        if(!$this->validPost())return $this->index('',$this->emailText('email_session_expired'),$draft);
        $audience=trim($draft['audience']);$subject=trim($draft['subject']);$message=trim($draft['message']);
        if(!in_array($audience,array('everyone','admins','lecturers','students'),true)||$subject===''||$message==='')
            return $this->index('',$this->emailText('email_required'),$draft);
        if(mb_strlen($subject)>255||mb_strlen($message)>10000)
            return $this->index('',$this->emailText('email_too_long'),$draft);
        try {
            $result=$this->mailer->send($audience,$subject,$message,$this->service->maintenance());
            $values=array('{count}'=>(string)(int)$result['count'],'{total}'=>(string)(int)$result['total']);
            if(!empty($result['ok'])){$this->storeEmailDraft($draft,'queued');return $this->index($this->emailText('email_queued',$values),'',$draft);}
            if((int)$result['count']===0&&empty($result['uncertain']))
                return $this->index('',$this->emailText('email_not_queued',array('{reason}'=>$result['reason'])),$draft);
            $this->storeEmailDraft($draft,'uncertain');
            return $this->index('',$this->emailText('email_incomplete',$values),$draft);
        } catch(Throwable $error) {
            $this->storeEmailDraft($draft,'uncertain');
            log_debug('System Maintenance email queueing failed: '.$error->getMessage());
            return $this->index('',$this->emailText('email_unknown'),$draft);
        }
    }
    /** Resolve email feedback through the language and system-text service. */
    private function emailText($key,array $values=array())
    {
        return strtr($this->getObject('language','language')->code2Txt('mod_systemmanagement_'.$key,'systemmanagement'),$values);
    }
    /** Delete one scheduled notice. */
    private function deleteNotice(){if(!$this->validPost())return $this->index('','Your session expired.');$id=$this->param('id');if(preg_match('/^[a-f0-9]{32}$/',$id))$this->notices->removeNotice($id);return $this->index('Notice removed.');}
    /** Render the deliberately minimal public maintenance page. */
    private function offline(){$maintenance=$this->service->maintenance();if(!$maintenance['active']||$this->user->isAdmin()){header('Location: '.$this->uri(array(),'_default'));exit;}$this->setLayoutTemplate(null);$this->setVar('systemMaintenance',$maintenance);return 'offline_tpl.php';}
    /** Keep each administrator's draft in their signed-in session, never in a shared browser store. */
    private function emailDraft(){return $this->getSession('email_draft_'.$this->user->userId(),null);}
    private function storeEmailDraft(array $content,$state='draft')
    {
        $this->setSession('email_draft_'.$this->user->userId(),array('content'=>$content,'state'=>$state));
    }
    /** Save a draft explicitly without calling Communications or changing the plan. */
    private function saveEmailDraft()
    {
        if(!$this->validPost())return $this->index('',$this->emailText('email_session_expired'));
        return $this->index($this->emailText('draft_saved'));
    }
    /** Validate the mutation before accepting a carried draft from any console form. */
    private function validPost()
    {
        if(strtoupper((string)($_SERVER['REQUEST_METHOD']??''))!=='POST')return false;
        $content=$this->getParam('email_draft',null);
        if($content===null&&$this->getParam('email_message',null)!==null){
            $content=array('audience'=>$this->getParam('audience',''),'subject'=>$this->getParam('subject',''),'message'=>$this->getParam('email_message',''));
        }
        if(is_array($content)){
            $draft=array();
            foreach(array('audience'=>32,'subject'=>1000,'message'=>20000) as $field=>$limit){
                if(!isset($content[$field])||!is_scalar($content[$field])||mb_strlen((string)$content[$field])>$limit)return false;
                $draft[$field]=(string)$content[$field];
            }
            $this->pendingEmailDraft=$draft;
        }
        if(!$this->csrf->consume(self::CSRF,$this->param('csrf_token')))return false;
        if($this->pendingEmailDraft!==null){
            $previous=$this->emailDraft();
            $this->storeEmailDraft($draft,isset($previous['content'])&&$previous['content']===$draft?$previous['state']:'draft');
        }
        return true;
    }
    /** Read one trimmed scalar request value. */
    private function param($name){$value=$this->getParam($name,'');return is_scalar($value)?trim((string)$value):'';}
}
?>
