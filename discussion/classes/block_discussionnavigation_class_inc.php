<?php
if (empty($GLOBALS['kewl_entry_point_run'])) die('You cannot view this page directly');
/** Role-aware navigation for the Discussion workspace. */
class block_discussionnavigation extends ChisimbaObject
{
 public function init(){$this->language=$this->getObject('language','language');$this->user=$this->getObject('user','security');$this->context=$this->getObject('dbcontext','context');$this->discussions=$this->getObject('dbdiscussion','discussion');$this->icons=$this->getObject('iconservice','ui');$this->title=$this->language->languageText('mod_discussion_navigation_title','discussion','Discussion tools');}
 public function show(){
  $id=trim((string)$this->getParam('id',''));$action=trim((string)$this->getParam('action',''));$discussion=$id!==''?$this->discussions->getDiscussion($id):null;$contextCode=$this->context->getContextCode();$mayManage=$this->user->isAdmin()||($contextCode!==''&&$this->user->isCourseAdmin($contextCode));$e=fn($v)=>htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');$links=array(array('messages-square',$this->language->languageText('mod_discussion_navigation_overview','discussion','All discussions'),$this->uri(array(),'discussion'),$action===''));
  if(is_array($discussion)&&!empty($discussion['id'])){$mayStart=$discussion['discussionlocked']!=='Y'&&($discussion['studentstarttopic']==='Y'||$mayManage);if($mayStart)$links[]=array('message-square-plus',$this->language->languageText('mod_discussion_startnewtopic','discussion','Start a new topic'),$this->uri(array('action'=>'newtopic','id'=>$id),'discussion'),$action==='newtopic');if($mayManage){$links[]=array('chart-no-axes-column',$this->language->languageText('word_statistics','system','Statistics'),$this->uri(array('action'=>'statistics','id'=>$id),'discussion'),$action==='statistics');if(($discussion['assessment_enabled']??'N')==='Y'){$links[]=array('clipboard-check',$this->language->languageText('mod_discussion_marking_title','discussion','Mark discussion'),$this->uri(array('action'=>'markdiscussion','id'=>$id),'discussion'),$action==='markdiscussion');}}}
  if($mayManage)$links[]=array('settings',$this->language->languageText('mod_discussion_navigation_administration','discussion','Discussion administration'),$this->uri(array('action'=>'administration'),'discussion'),$action==='administration');
  $html='<nav class="discussion-nav" aria-label="'.$e($this->title).'">';foreach($links as $link){$content=$this->icons->render($link[0],array('decorative'=>true)).'<span>'.$e($link[1]).'</span>';$html.=$link[3]?'<span class="discussion-nav__current" aria-current="page">'.$content.'</span>':'<a href="'.$e($link[2]).'">'.$content.'</a>';}return $html.'</nav>';
 }
}
?>
