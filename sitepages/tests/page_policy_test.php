<?php
$GLOBALS['kewl_entry_point_run']=true;
class ChisimbaObject { public array $objects=[];public function getObject($name,$module){return $this->objects[$name];} }
require dirname(__DIR__).'/classes/pagepolicy_class_inc.php';
$user=new class {public bool $logged=false,$admin=false;public function isLoggedIn(){return $this->logged;}public function isAdmin(){return $this->admin;}public function userId(){return 'editor';}};
$rights=new class {public bool $defined=true,$granted=false;public function areaIdForName($app,$area){if($app!=='chisimba'||$area!=='sitepages')throw new Exception('Wrong area');return $this->defined?1:null;}public function rightIdForArea($area,$right){if($right!=='manage')throw new Exception('Wrong right');return 2;}public function isGranted($user,$right){return $this->granted;}};
$p=new pagepolicy();$p->objects=['user'=>$user,'permissionservice'=>$rights];$p->init();
function check($value,$message){if(!$value)throw new Exception($message);}
check(!$p->canManage(),'Anonymous allowed');$user->logged=true;check(!$p->canManage(),'Ordinary member allowed');$rights->granted=true;check($p->canManage(),'Editor denied');$rights->granted=false;check(!$p->canManage(),'Revoked permission retained');$rights->defined=false;check(!$p->canManage(),'Missing definition allowed');$user->admin=true;check($p->canManage(),'Administrator denied');$user->logged=false;check(!$p->canManage(),'Logged-out admin allowed');echo "PASS: anonymous, member, editor, revoked, undefined and administrator page policy\n";
