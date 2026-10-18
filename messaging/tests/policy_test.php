<?php
$GLOBALS['kewl_entry_point_run']=true;
class ChisimbaObject {public function getObject($name,$module=null){return $GLOBALS['objects'][$name];}}
require dirname(__DIR__).'/classes/conversationpolicy_class_inc.php';
$user=new class {public $logged=true,$admin=false,$id='member',$lecturer=false;public function userId(){return $this->id;}public function isLoggedIn(){return $this->logged;}public function isAdmin(){return $this->admin;}public function isContextLecturer($user,$context){return $this->lecturer&&$context==='owned';}public function isCourseAdmin($context){return false;}};
$members=new class {public $joined=['owned'];public function isContextMember($user,$context){return in_array($context,$this->joined,true);}public function getUserContext($user){return $this->joined;}};
$GLOBALS['objects']=['user'=>$user,'usercontext'=>$members,'dbcontext'=>new class {public function getContextDetails($code){return in_array($code,['owned','other'],true)?['contextcode'=>$code]:null;}}];
$p=new conversationpolicy();$p->init();function check($ok,$label){if(!$ok)throw new RuntimeException($label);echo "PASS $label\n";}
check($p->mayRead('owned')&&!$p->mayRead('other'),'members cannot read another context');check(!$p->mayModerate('owned'),'ordinary members cannot create channels');
$user->lecturer=true;check($p->mayModerate('owned')&&!$p->mayModerate('other'),'staff authority follows persisted context');
$user->lecturer=false;$members->joined=[];check(!$p->mayRead('owned'),'membership revocation');
$user->admin=true;check($p->mayRead('other')&&!$p->mayRead('missing'),'administrator still requires a real context');
$user->logged=false;check(!$p->mayRead('owned'),'anonymous blocked');
