<?php
if(empty($GLOBALS['kewl_entry_point_run']))die('No direct access');
class helpcontent extends ChisimbaObject
{
 public function init(){}
 public function mayViewTopic($id){return $id==='pages'&&$this->getObject('pagepolicy','sitepages')->canManage();}
 public function getTopic($id){$blocks=$this->getObject('compositionservice','contentblocks');$l=$this->getObject('language','language');$t=fn($k)=>$l->languageText('mod_sitepages_help_'.$k,'sitepages');return $id==='pages'?['title'=>$t('title'),'summary'=>$t('summary'),'steps'=>[$t('steps')],'sections'=>[['heading'=>$blocks->text('audio'),'body'=>$blocks->text('audio_help')],['heading'=>$blocks->text('video_gallery'),'body'=>$blocks->text('gallery_help')],['heading'=>$t('title'),'body'=>$t('recovery')]]]:null;}
}
