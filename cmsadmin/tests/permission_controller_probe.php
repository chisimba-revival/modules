<?php
if (PHP_SAPI !== 'cli') exit(64);
ob_start(); require __DIR__.'/permission_request_test.php'; ob_end_clean();
[$script,$role,$method,$token,$action]=$argv;
ChisimbaObject::$role=$role; $_SERVER['REQUEST_METHOD']=$method; http_response_code(200);
class controller extends ChisimbaObject {
 public static array $writes=[];
 public function getParam($key,$default=null){global $token,$action;return ['action'=>$action,'id'=>'record-one','drp_owner'=>'user-two','chk_frontpage'=>'on','csrf_token'=>$token,'confirm'=>'yes'][$key]??$default;}
 public function getObject($name,$module){
  if($name==='permissionrequest') return new permissionrequest();
  if($name==='language')return new class {function languageText($key,$module){return $key;}};
  return parent::getObject($name,$module);
 }
 public function setLayoutTemplate($value){}
 public function setVar($key,$value){}
 public function nextAction($action,$params=[],$module=null){return null;}
}
require __DIR__.'/../controller.php';
class PermissionControllerFixture extends cmsadmin {
 public function prepare(){
  $this->_objUser=new class {function userId(){return 'fixture';} function inAdminGroup($id,$group){return true;}};
  $this->_objUserPerm=new class {
   function deleteRecord($id){controller::$writes[]=['delete',$id];}
   function editUserPermission($id,$user,$front){controller::$writes[]=['edit',$id,$user,$front];}
  };
 }
}
$c=new PermissionControllerFixture();$c->prepare();
register_shutdown_function(static function(){echo 'RESULT:'.json_encode(['status'=>http_response_code(),'writes'=>controller::$writes]);});
$c->dispatch();
