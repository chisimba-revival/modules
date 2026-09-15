<?php
/** Public recordings journey help, supplied through canonical Help. @author Derek Keats */
if(empty($GLOBALS['kewl_entry_point_run']))die('No direct access');
class helpcontent extends ChisimbaObject
{
 public function init(){}
 public function mayViewTopic($id){return $id==='recordings'||($id==='editing'&&$this->getObject('webinareditpolicy','webinar')->canManage());}
 public function getTopic($id){if(!$this->mayViewTopic($id))return null;$r=$this->getObject('webinarrenderer','webinar');if($id==='editing')return ['title'=>$r->text('editing_help_title'),'summary'=>$r->text('editing_help_summary'),'steps'=>array_map(fn($key)=>$r->text('editing_help_'.$key),['content','schedule','classification','save','join'])];return ['title'=>$r->text('videos_help_title'),'summary'=>$r->text('videos_help_summary'),'steps'=>[$r->text('videos_help_play'),$r->text('videos_help_more'),$r->text('videos_help_subscribe')]];}
}
