<?php
/** Reusable publishing block for any Chisimba block placement. @author Derek Keats */
if(empty($GLOBALS['kewl_entry_point_run']))die('No direct access');
class block_createblog extends ChisimbaObject
{
    public $title;
    private $type='site'; private $scope='site'; private $exclude='';
    public function init() { $this->title=$this->getObject('publishingpolicy','simpleblog')->canCreate('site','site')?$this->getObject('publishingrenderer','simpleblog')->text('sidebar_create'):''; }
    public function configure($type,$scope,$exclude='') { $this->type=$type;$this->scope=$scope;$this->exclude=$exclude;$this->title=$this->getObject('publishingpolicy','simpleblog')->canCreate($type,$scope)?$this->getObject('publishingrenderer','simpleblog')->text('sidebar_create'):''; }
    public function show() { return $this->getObject('publishingsidebar','simpleblog')->render('create',$this->type,$this->scope,$this->exclude); }
}
