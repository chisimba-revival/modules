<?php
/** Public archive controller. No mutations or attendee data are exposed. @author Derek Keats */
if(empty($GLOBALS['kewl_entry_point_run']))die('No direct access');
class webinar extends controller
{
 public function init(){}
 public function requiresLogin($action=null){return false;}
 public function dispatch($action=null){
  $action=$this->getParam('action','archive');
  $record=null;
  if($action==='view')$record=$this->getObject('webinarstore')->one($this->getParam('id',''));
  if(!in_array($action,['archive','view','speakers'],true)||($action==='view'&&!$record)){http_response_code(404);$this->setVar('webinarMissing',true);}
  $this->setVar('webinarRecord',$record);
  $this->setVar('webinarAction',$action);
  return 'archive_tpl.php';
 }
}
