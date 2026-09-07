<?php
if (empty($GLOBALS['kewl_entry_point_run'])) die('You cannot view this page directly');
/** Modern wide dashboard preview of one discussion. */
class dynamicblocks_discussionview extends ChisimbaObject
{
 public function init(){$this->language=$this->getObject('language','language');$this->topics=$this->getObject('dbtopic','discussion');$this->discussions=$this->getObject('dbdiscussion','discussion');$this->user=$this->getObject('user','security');$this->icons=$this->getObject('iconservice','ui');}
 public function renderDiscussion($id){
  $discussion=$this->discussions->getDiscussion($id);if(!is_array($discussion)||empty($discussion['id']))return '';$items=(array)$this->topics->showTopicsInDiscussion($id,$this->user->userId(),$discussion['archivedate'],null,null,null,null);$e=fn($v)=>htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');$mayStart=$discussion['discussionlocked']!=='Y'&&($discussion['studentstarttopic']==='Y'||$this->user->isCourseAdmin($discussion['discussion_context']));
  $html='<section class="discussion-dashboard" aria-labelledby="discussion-dashboard-'.$e($id).'"><header><div class="discussion-dashboard__icon">'.$this->icons->render('messages-square',array('decorative'=>true)).'</div><div><p class="discussion-dashboard__eyebrow">'.$e($this->language->languageText('mod_discussion_dashboard_eyebrow','discussion','Discussion')).'</p><h2 id="discussion-dashboard-'.$e($id).'">'.$e($discussion['discussion_name']).'</h2></div><span class="discussion-dashboard__count">'.count($items).' '.$e($this->language->languageText(count($items)===1?'word_topic':'word_topics','system',count($items)===1?'Topic':'Topics')).'</span></header>';
  if(!$items)$html.='<div class="discussion-empty-state"><span class="discussion-empty-state__icon">'.$this->icons->render('message-circle',array('decorative'=>true)).'</span><div><h3>'.$e($this->language->languageText('mod_discussion_no_topics_title','discussion','No topics yet')).'</h3><p>'.$e($this->language->languageText('mod_discussion_no_topics_help','discussion','Start the conversation when you are ready.')).'</p></div></div>';
  else{$html.='<div class="discussion-dashboard__topics">';foreach(array_slice($items,0,5) as $topic){$url=$this->uri(array('module'=>'discussion','action'=>'viewtopic','id'=>$topic['topic_id']));$html.='<a href="'.$e($url).'"><span>'.$e(stripslashes($topic['post_title'])).'</span><small>'.$e((int)$topic['replies'].' '.$this->language->languageText('word_replies','system','Replies')).'</small></a>';}$html.='</div>';}
  $html.='<footer><a class="button chisimba-button-secondary" href="'.$e($this->uri(array('module'=>'discussion','action'=>'discussion','id'=>$id))).'">'.$this->icons->render('messages-square',array('decorative'=>true)).' '.$e($this->language->languageText('mod_discussion_open_discussion','discussion','Open discussion')).'</a>';
  if($mayStart)$html.='<a class="button" href="'.$e($this->uri(array('module'=>'discussion','action'=>'newtopic','id'=>$id))).'">'.$this->icons->render('message-square-plus',array('decorative'=>true)).' '.$e($this->language->languageText('mod_discussion_startnewtopic','discussion','Start a new topic')).'</a>';
  return $html.'</footer></section>';
 }
 public function isValid($action){return true;}
}
?>
