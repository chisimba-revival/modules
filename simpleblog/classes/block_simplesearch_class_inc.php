<?php
/** Publishing block using the shared permission-aware reader. */
if (empty($GLOBALS['kewl_entry_point_run'])) die('No direct access');
class block_simplesearch extends ChisimbaObject
{
 public $title;
 public function init() { $this->title=$this->getObject('publishingrenderer','simpleblog')->text('search'); }
 public function show() { $r=$this->getObject('publishingrenderer','simpleblog'); $type='site'; $scope=(string)('site');return $r->button('posts','newspaper',array('scope'=>$type,'blogid'=>$scope)); }
}
