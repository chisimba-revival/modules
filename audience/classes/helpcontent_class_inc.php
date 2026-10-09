<?php
if(empty($GLOBALS['kewl_entry_point_run']))die('No direct access');
class helpcontent extends ChisimbaObject {
 public function init(){}
 public function mayViewTopic($id){return $id==='administration'&&$this->getObject('audienceadmin','audience')->allowed();}
 public function getTopic($id){if(!$this->mayViewTopic($id))return null;$r=$this->getObject('audiencerenderer','audience');return ['title'=>$r->text('help_title'),'summary'=>$r->text('scope'),'steps'=>array_map(fn($k)=>$r->text($k),['help_users','help_compose','banner_help','help_send','schedule_help','help_recovery'])];}
}
