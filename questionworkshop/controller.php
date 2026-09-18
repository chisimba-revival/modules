<?php
/** Compatibility route for saved Multiple Choice Generator links. */
if(empty($GLOBALS['kewl_entry_point_run']))die('No direct access');
class questionworkshop extends controller
{
 public function requiresLogin($action=null){return true;}
 public function dispatch($action=null){
  $params=[];
  foreach(['id','format','answers'] as $key){$value=$this->getParam($key);if(is_string($value))$params[$key]=$value;}
  $action=$this->getParam('action','list');
  if(!in_array($action,['list','view','download'],true))$action='view';
  // Never replay a write or a chargeable AI request through an old form.
  return $this->nextAction($action,$params,'mcqgenerator');
 }
}
