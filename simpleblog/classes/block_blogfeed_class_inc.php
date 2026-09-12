<?php
/** Reusable publishing block for any Chisimba block placement. @author Derek Keats */
if(empty($GLOBALS['kewl_entry_point_run']))die('No direct access');
class block_blogfeed extends ChisimbaObject
{
    public $title;
    private $type='site'; private $scope='site'; private $exclude='';
    public function init() { $this->title=$this->getObject('publishingrenderer','simpleblog')->text('sidebar_feed'); }
    public function configure($type,$scope,$exclude='') { $this->type=$type;$this->scope=$scope;$this->exclude=$exclude; }
    public function show() { return $this->getObject('publishingsidebar','simpleblog')->render('feed',$this->type,$this->scope,$this->exclude); }
}
