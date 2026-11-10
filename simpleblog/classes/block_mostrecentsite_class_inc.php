<?php
/** Publishing block using the shared permission-aware reader. */
if (empty($GLOBALS['kewl_entry_point_run'])) die('No direct access');
class block_mostrecentsite extends ChisimbaObject
{
 public $title;
 public function init() { $this->title=$this->getObject('publishingrenderer','simpleblog')->text('latest_card'); }
 public function show() { $r=$this->getObject('publishingrenderer','simpleblog'); $type='site'; $scope=(string)('site');return $r->posts($type,$scope,false,1,'posted','','',0,0,true); }
}
