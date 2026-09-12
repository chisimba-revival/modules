<?php
/** Publishing block using the shared permission-aware reader. */
if (empty($GLOBALS['kewl_entry_point_run'])) die('No direct access');
class block_personalblog extends ChisimbaObject
{
 public $title;
 public function init() { $this->title=$this->getObject('publishingrenderer','simpleblog')->text('personal_posts'); }
 public function show() { $r=$this->getObject('publishingrenderer','simpleblog'); $type='personal'; $scope=(string)($this->getObject('user','security')->userId());return $r->posts($type,$scope); }
}
