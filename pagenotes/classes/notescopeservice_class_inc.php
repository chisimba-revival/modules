<?php
/**
 * Resolves personal, course and site note scopes.
 *
 * @author Derek Keats
 * @package pagenotes
 */
if(empty($GLOBALS['kewl_entry_point_run']))die('You cannot view this page directly');
/** Produces one explicit stable scope for every Notes request. */
class notescopeservice extends controller
{
 private $user;
 private $context;
 /** Initialise scope dependencies. */
 public function init(){$this->user=$this->getObject('user','security');$this->context=$this->getObject('dbcontext','context');}
 /** Resolve a requested scope without granting permission to it. */
 public function resolve($requested='',$requestedId=''){$code=(string)$this->context->getContextCode();if($requested==='context'&&$requestedId!=='')$code=(string)$requestedId;if($requested==='site')return array('type'=>'site','id'=>'root','label'=>'Site notes');if($requested==='context'||($requested===''&&$code!==''&&$code!=='root'))return array('type'=>'context','id'=>$code,'label'=>'Course notes');return array('type'=>'personal','id'=>(string)$this->user->userId(),'label'=>'My notes');}
}
?>
