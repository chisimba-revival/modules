<?php
/**
 * Sends explicit administrator-approved maintenance email notices.
 *
 * @author Derek Keats
 * @package systemmanagement
 */
if (empty($GLOBALS['kewl_entry_point_run'])) die('You cannot view this page directly');
class systemmanagementmailer extends ChisimbaObject
{
    private $recipients; private $clock; private $user; private $site; private $communications; private $config;
    /** Load recipient, date, identity and site-branding services. */
    public function init(){$this->recipients=$this->getObject('dbsystemnotices');$this->clock=$this->getObject('timeanddateservice','timeanddate-service');$this->user=$this->getObject('user','security');$this->site=$this->getObject('altconfig','config');$this->communications=$this->getObject('communicationservice','communications');$this->config=$this->getObject('dbsysconfig','sysconfig');}
    /** Return recipient counts without exposing addresses in the interface. */
    public function audienceCounts(){$counts=array();foreach(array('everyone','admins','lecturers','students') as $audience)$counts[$audience]=count($this->recipients->recipientEmails($audience));return $counts;}
    /** Queue per-recipient notices and report confirmed progress even if a batch stops. */
    public function send($audience,$subject,$message,array $maintenance)
    {
        $emails=$this->recipients->recipientEmails($audience);
        $outcome=array('ok'=>false,'count'=>0,'total'=>count($emails),'batchId'=>'','uncertain'=>false,'reason'=>'');
        if(!$emails){$outcome['reason']=$this->text('email_no_recipients');return $outcome;}
        $readiness=$this->readiness();
        if(!$readiness['ready']){$outcome['reason']=$readiness['message'];return $outcome;}
        $siteName=trim((string)$this->site->getSiteName());
        $start=trim((string)($maintenance['start']??''));$end=trim((string)($maintenance['end']??''));
        $window=$this->text('email_unplanned');
        if($start!==''&&$end!==''){
            $window=$this->text('email_planned_window')."\n".$this->text('email_start').': '.$this->clock->formatDateTime($maintenance['start'])
                ."\n".$this->text('email_end').': '.$this->clock->formatDateTime($maintenance['end']);
        }
        $body=trim($message)."\n\n".$window."\n\n".$siteName;
        $batchId=bin2hex(random_bytes(16));$outcome['batchId']=$batchId;
        foreach($emails as $email){
            try {
                $result=$this->communications->queueEmail(array('to'=>$email,'subject'=>$subject,'text'=>$body,
                    'idempotencyKey'=>'maintenance-'.$batchId.'-'.hash('sha256',$email),
                    'metadata'=>array('purpose'=>'maintenance_notice','batch_id'=>$batchId,'audience'=>$audience,'requested_by'=>$this->user->userId())));
            } catch(Throwable $error) {
                // An exception may occur after insertion: do not claim that nothing queued.
                log_debug('Maintenance email batch '.$batchId.' queue status uncertain: '.$error->getMessage());
                $outcome['uncertain']=true;return $outcome;
            }
            if(empty($result['ok'])){
                log_debug('Maintenance email batch '.$batchId.' stopped: '.($result['code']??'queue_failed'));
                $outcome['reason']=$this->text('email_queue_failed');return $outcome;
            }
            $outcome['count']++;
        }
        $outcome['ok']=true;return $outcome;
    }
    /** Resolve maintenance email labels through the shared language system. */
    private function text($key)
    {
        return $this->getObject('language','language')->code2Txt('mod_systemmanagement_'.$key,'systemmanagement');
    }
    /** Describe whether the canonical Communications transport can accept mail. */
    public function readiness(){$transport=strtolower(trim((string)$this->config->getValue('COMMUNICATION_TRANSPORT','communications')));$sender=trim((string)$this->config->getValue('COMMUNICATION_FROM_EMAIL','communications'));if($transport!=='sendgrid')return array('ready'=>false,'transport'=>$transport?:'not configured','message'=>'Outbound email is disabled until a production Communications transport is configured.');if(filter_var($sender,FILTER_VALIDATE_EMAIL)===false)return array('ready'=>false,'transport'=>$transport,'message'=>'Communications needs a valid verified sender email address.');if(trim((string)$this->config->getValue('COMMUNICATION_SENDGRID_API_KEY','communications'))==='')return array('ready'=>false,'transport'=>$transport,'message'=>'Communications needs a SendGrid API key.');return array('ready'=>true,'transport'=>$transport,'message'=>'Outbound email is ready.');}
    /** Return recent maintenance delivery records for the administrator console. */
    public function recentDeliveries($limit=25){return $this->recipients->recentEmailDeliveries($limit);}
}
?>
