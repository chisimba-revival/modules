<?php
/** Exercise the real controller gate with synthetic roles, without a database. */
$GLOBALS['kewl_entry_point_run']=true;
class controller {}
require dirname(__DIR__).'/controller.php';
$module=new mcqtests();
$module->objUser=new class { public $admin=false; public $loggedIn=true; public function isAdmin(){return $this->admin;} public function isLoggedIn(){return $this->loggedIn;} };
$module->objContextGroups=new class { public $lecturer=false; public function isContextLecturer(){return $this->lecturer;} };
$module->objCond=new class { public $member=true; public function isContextMember($role){return $role==='Students' && $this->member;} };
function check($ok,$label){if(!$ok)throw new RuntimeException($label);echo 'PASS '.$label."\n";}
foreach(array('','newhome','answertest','continuetest','marktest','showtest') as $action) check($module->isValid($action),'learner allowed '.$action);
foreach(array('add','edit','view','view2','viewnumericalquestions','deletequestion2','liststudents','doexport','unknown') as $action) check(!$module->isValid($action),'learner denied '.$action);
$module->objCond->member=false;check(!$module->isValid('answertest'),'nonmember denied');
$module->objCond->member=true;$module->objUser->loggedIn=false;check(!$module->isValid('answertest'),'anonymous denied');
$module->objUser->loggedIn=true;$module->objContextGroups->lecturer=true;check($module->isValid('add'),'lecturer retains authoring');
