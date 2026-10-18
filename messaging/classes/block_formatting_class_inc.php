<?php
/** Legacy block placement now links to the native workspace. @author Derek Keats */
if (empty($GLOBALS['kewl_entry_point_run'])) die('No direct access');
class block_formatting extends ChisimbaObject {
 public $title;
 public function init() {$this->title=$this->getObject('conversationview')->text('title');}
 public function show() {return $this->getObject('conversationview')->entryLink();}
}
