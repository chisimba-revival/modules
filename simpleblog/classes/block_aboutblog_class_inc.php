<?php
/** Publishing block using the shared permission-aware reader. */
if (empty($GLOBALS['kewl_entry_point_run'])) die('No direct access');
class block_aboutblog extends ChisimbaObject
{
 public $title;
 public function init() { $this->title=$this->getObject('publishingrenderer','simpleblog')->text('personal'); }
 public function show() { $r=$this->getObject('publishingrenderer','simpleblog'); $type='personal'; $scope=(string)($this->getObject('user','security')->userId());return $r->button('posts','newspaper',array('scope'=>$type,'blogid'=>$scope)); }
}
