<?php
/** Contextual publishing guidance. */
if (empty($GLOBALS['kewl_entry_point_run'])) die('No direct access');
class helpcontent extends ChisimbaObject
{
 public function init() {}
 public function mayViewTopic($topic) { return $topic==='publishing' && $this->getObject('user','security')->isLoggedIn(); }
 public function getTopic($topic) {
  if($topic!=='publishing')return null;
  $r=$this->getObject('publishingrenderer');
  return array('title'=>$r->text('help_title'),'summary'=>$r->text('help_summary'),
   'steps'=>array($r->text('help_scope'),$r->text('help_write'),$r->text('help_preview'),$r->text('help_publish')),
   'sections'=>array(array('heading'=>$r->text('author'),'body'=>$r->text('help_author')),array('heading'=>$r->text('permissions'),'body'=>$r->text('help_permissions'))));
 }
}
