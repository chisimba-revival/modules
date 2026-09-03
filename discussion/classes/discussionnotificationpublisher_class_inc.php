<?php
/** Publishes Discussion domain events without owning delivery channels. @author Derek Keats @package discussion */
if(empty($GLOBALS['kewl_entry_point_run']))die('You cannot view this page directly');
class discussionnotificationpublisher extends ChisimbaObject
{
 private $notifications;private $discussionSubscriptions;private $topicSubscriptions;
 /** Load the notification boundary and existing subscription sources. */
 public function init(){$this->notifications=$this->getObject('notificationservice','notifications');$this->discussionSubscriptions=$this->getObject('dbdiscussionsubscriptions','discussion');$this->topicSubscriptions=$this->getObject('dbtopicsubscriptions','discussion');}
 /** Publish a topic or reply notification to current subscribers, excluding its author. */
 public function postCreated($postId,$topicId,$discussionId,$contextCode,$authorId,$authorName,$topicTitle,$discussionName,$targetUrl,$isReply){$recipients=$this->discussionSubscriptions->recipientUserIds($discussionId);if($isReply)$recipients=array_merge($recipients,$this->topicSubscriptions->recipientUserIds($topicId));$recipients=array_values(array_diff(array_unique($recipients),array((string)$authorId)));if(!$recipients)return array('ok'=>true,'code'=>'no_recipients','recipientCount'=>0);$targetUrl=$this->notificationTarget($contextCode,$topicId,$postId,$targetUrl);return $this->notifications->publish(array('idempotencyKey'=>'discussion-post:'.$postId,'type'=>$isReply?'discussion.reply.created':'discussion.topic.created','actorUserId'=>$authorId,'contextCode'=>$contextCode,'sourceType'=>'discussion_post','sourceId'=>$postId,'recipientUserIds'=>$recipients,'title'=>$isReply?'New reply in '.$topicTitle:'New topic in '.$discussionName,'summary'=>$authorName.($isReply?' replied to a discussion you follow.':' started a discussion you follow.'),'targetUrl'=>html_entity_decode((string)$targetUrl,ENT_QUOTES,'UTF-8'),'payload'=>array('discussionId'=>$discussionId,'topicId'=>$topicId,'postId'=>$postId)));}
 /** Enter the correct course before opening a notification from another scope. */
 public function notificationTarget($contextCode,$topicId,$postId,$fallback){
  if(trim((string)$contextCode)===''||$contextCode==='root')return html_entity_decode((string)$fallback,ENT_QUOTES,'UTF-8');
  $target=$this->getObject('courseawarelaunchservice','context')->target($contextCode,'discussion',array('action'=>'viewtopic','id'=>$topicId,'post'=>$postId,'type'=>'context'));
  return html_entity_decode($this->uri($target['params'],$target['module']),ENT_QUOTES,'UTF-8');
 }
}
?>
