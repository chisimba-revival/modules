<?php
/** Publishing block using the shared permission-aware reader. */
if (empty($GLOBALS['kewl_entry_point_run'])) die('No direct access');
class block_contextblog extends ChisimbaObject
{
 public $title;
 public function init() { $this->title=$this->getObject('publishingrenderer','simpleblog')->text('posts'); }
 public function show() { $r=$this->getObject('publishingrenderer','simpleblog'); $type='context'; $scope=(string)($this->getObject('dbcontext','context')->getContextCode());$actions=$this->getObject('publishingpolicy','simpleblog')->canCreate($type,$scope)?$r->button('manage','list',array('action'=>'manage','scope'=>$type,'blogid'=>$scope)):'';return $actions.$r->posts($type,$scope); }
}
