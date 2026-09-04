<?php
/** Publish explicitly selected announcement delivery through Updates. @author Derek Keats */
if (empty($GLOBALS['kewl_entry_point_run'])) die('You cannot view this page directly');
class announcementnotificationpublisher extends ChisimbaObject
{
    private $announcements; private $notifications;
    public function init(){$this->announcements=$this->getObject('dbannouncements');$this->notifications=$this->getObject('notificationservice','notifications');}
    public function publish(array $announcement,array $contexts=array()){
        $recipients=$this->announcements->recipientUserIds($announcement['audience'],$contexts);
        $actor=(string)$this->getObject('user','security')->userId();
        $recipients=array_values(array_diff($recipients,array($actor)));
        if(!$recipients)return array('ok'=>true,'code'=>'no_recipients','recipientCount'=>0);
        $summary=trim((string)$announcement['summary']);if($summary==='')$summary=trim(strip_tags((string)$announcement['message']));
        return $this->notifications->publish(array('idempotencyKey'=>'announcement:'.$announcement['id'],'type'=>'announcement.'.$announcement['announcement_type'].'.published','actorUserId'=>$actor,'sourceType'=>'announcement','sourceId'=>$announcement['id'],'recipientUserIds'=>$recipients,'title'=>$announcement['title'],'summary'=>mb_substr($summary,0,10000),'targetUrl'=>html_entity_decode($this->uri(array('action'=>'view','id'=>$announcement['id']),'announcements'),ENT_QUOTES,'UTF-8'),'payload'=>array('announcementType'=>$announcement['announcement_type'],'audience'=>$announcement['audience'])));
    }
}
