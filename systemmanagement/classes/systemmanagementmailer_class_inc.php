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
    private $recipients; private $clock; private $user; private $site;
    /** Load recipient, date, identity and site-branding services. */
    public function init(){$this->recipients=$this->getObject('dbsystemnotices');$this->clock=$this->getObject('timeanddateservice','timeanddate-service');$this->user=$this->getObject('user','security');$this->site=$this->getObject('altconfig','config');}
    /** Return recipient counts without exposing addresses in the interface. */
    public function audienceCounts(){$counts=array();foreach(array('everyone','admins','lecturers','students') as $audience)$counts[$audience]=count($this->recipients->recipientEmails($audience));return $counts;}
    /** Send one maintenance notice and return its target-recipient count. */
    public function send($audience,$subject,$message,array $maintenance){$emails=$this->recipients->recipientEmails($audience);if(!$emails)throw new RuntimeException('No active users with valid email addresses match that audience.');$siteName=trim((string)$this->site->getSiteName());$window="Planned maintenance window:\nStart: ".$this->clock->formatDateTime($maintenance['start'])."\nEnd: ".$this->clock->formatDateTime($maintenance['end']);$body=trim($message)."\n\n".$window."\n\n".$siteName;$mailer=$this->getObject('email','mail');$sender=trim((string)$this->user->email());if(filter_var($sender,FILTER_VALIDATE_EMAIL)===false)throw new RuntimeException('The administrator account needs a valid email address before notices can be sent.');$mailer->setValue('to',$sender);$mailer->setValue('bcc',$emails);$mailer->setValue('from',$sender);$mailer->setValue('fromName',$siteName);$mailer->setValue('subject',$subject);$mailer->setValue('body',$body);$mailer->setValue('AltBody',$body);if($mailer->send()===false)throw new RuntimeException('The mail service did not accept the notice.');return count($emails);}
}
?>
