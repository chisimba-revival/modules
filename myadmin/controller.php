<?php
if(empty($GLOBALS['kewl_entry_point_run']))die('You cannot view this page directly');
class myadmin extends controller
{
    private $blockEditor;
    /** Dependencies and state initialised by this component. */
    public $objLanguage;

    private $user;
    private $contextBlocks;
    private $csrf;
    public function init() {
        $this->blockEditor = $this->newObject('dashboardblockeditor', 'context')->forModule('myadmin');
        $this->user=$this->getObject('user','security');
        $this->contextBlocks=$this->getObject('dbcontextblocks','context');
        $this->objLanguage=$this->getObject('language','language');
        $this->csrf=$this->getObject('nativeauthwebcomposition','security')->build()['csrf'];
    }

    public function dispatch($action) {
        $blockAction=in_array($action,array('renderblock','addblock','removeblock','moveblock'),true);
        if($blockAction) {
            $this->dispatchBlockAction($action);
            return null;
        }
        if(!$this->blockEditor->canEdit())return 'noaccess_tpl.php';
        $notice='';
        $registrationNotice='';
        if($action==='runmailqueue')$notice=$this->runMailQueue();
        if($action==='sendregistrationreminder')$registrationNotice=$this->sendRegistrationReminder();
        if($action==='deleteunconfirmedregistrations')$registrationNotice=$this->deleteUnconfirmedRegistrations();
        $managing=$action==='manage';
        $this->setVar('managingDashboard',$managing);
        $this->setVar('administrationOverview',$this->getObject('administrationoverview')->show($this->csrf->issue('myadmin_mail_queue'),$this->csrf->issue('myadmin_registration_reminder'),$notice,$this->csrf->issue('myadmin_registration_delete'),$registrationNotice));
        $this->setVar('blockEditor', $this->blockEditor);

        $this->setVar('blockEditingRevision', $managing ? $this->contextBlocks->editingRevision('myadmin') : '');

        foreach(array('right'=>'upperBlocks','left'=>'lowerBlocks','middle'=>'wideBlocks') as $side=>$variable)$this->setVar($variable,$this->contextBlocks->getContextBlocks('myadmin',$side));
        $this->setVar('mayEditBlocks',$managing);
        $this->setVar('availableNarrowBlocks',$managing ? $this->blockEditor->options(false) : []);
        $this->setVar('availableWideBlocks',$managing ? $this->blockEditor->options(true) : []);
        return 'main_tpl.php';
    }

    private function runMailQueue(){if(strtoupper((string)($_SERVER['REQUEST_METHOD']??''))!=='POST'||!$this->csrf->consume('myadmin_mail_queue',(string)$this->getParam('csrf_token','')))return $this->objLanguage->languageText('mod_myadmin_queue_session','myadmin','Your session expired. Please try again.');if(!$this->getObject('mailqueuehealth')->workerMonitoringAvailable())return $this->objLanguage->languageText('mod_myadmin_mail_upgrade_help','myadmin','Update Communications in the module catalogue to install mail-worker monitoring before running the queue from this page.');$summary=$this->getObject('communicationworker','communications')->run(20);$values=array('selected'=>(int)$summary['selected'],'sent'=>(int)$summary['sent']);$message=$this->objLanguage->code2Txt('mod_myadmin_queue_run_result','myadmin',$values,'Mail queue checked: [-SELECTED-] selected and [-SENT-] sent.');return str_replace(array('[SELECTED]','[SENT]'),array($values['selected'],$values['sent']),$message);}
    private function sendRegistrationReminder(){if(strtoupper((string)($_SERVER['REQUEST_METHOD']??''))!=='POST'||!$this->csrf->consume('myadmin_registration_reminder',(string)$this->getParam('csrf_token','')))return $this->objLanguage->languageText('mod_myadmin_registration_reminder_session','myadmin','Your session expired. Please try again.');$result=$this->getObject('registrationservice','registration-service')->sendAdministratorReminder((string)$this->getParam('pending_id',''),$this->user->userId());return $this->objLanguage->languageText(!empty($result['ok'])?'mod_myadmin_registration_reminder_sent':'mod_myadmin_registration_reminder_failed','myadmin',!empty($result['ok'])?'The reminder was added to the mail queue.':'The reminder could not be sent. Check the mail queue and try again.');}
    private function deleteUnconfirmedRegistrations(){if(strtoupper((string)($_SERVER['REQUEST_METHOD']??''))!=='POST'||!$this->csrf->consume('myadmin_registration_delete',(string)$this->getParam('csrf_token','')))return $this->objLanguage->languageText('mod_myadmin_registration_delete_session','myadmin','Your session expired. Please try again.');$result=$this->getObject('registrationservice','registration-service')->deleteStaleAwaitingVerification($this->user->userId());if(empty($result['ok']))return $this->objLanguage->languageText('mod_myadmin_registration_delete_failed','myadmin','The pending registrations could not be deleted. Please try again.');$message=$this->objLanguage->code2Txt('mod_myadmin_registration_delete_success','myadmin',array('count'=>(int)$result['deleted']),'[-COUNT-] pending registrations deleted.');return str_replace('[-COUNT-]',(string)(int)$result['deleted'],$message);}

    /** Shared boundary enforces current administrator, POST, CSRF and fixed scope. */
    private function dispatchBlockAction($action)
    {
        $input = [];
        foreach (['contextcode','blockid','side','direction','revision','csrf_token'] as $key) {
            $input[$key] = $this->getParam($key);
        }
        $result = $this->blockEditor->handle($action, $input, $_SERVER['REQUEST_METHOD'] ?? '');
        http_response_code($result['status']);
        header('Content-Type: application/json; charset=UTF-8');
        header('Cache-Control: no-store');
        echo json_encode($result);
        exit;
    }
}
?>
