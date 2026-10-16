<?php
if (PHP_SAPI !== 'cli') exit(64);
ob_start(); require __DIR__.'/security_test.php'; ob_end_clean();
[$script,$role,$method,$token]=$argv;
ChisimbaObject::$role=$role;
$_SERVER['REQUEST_METHOD']=$method;
http_response_code(200);
class controller extends ChisimbaObject {
 public static int $writes=0;
 function getParam($key,$default=null){global $token;return ['action'=>'deletemapping','id'=>'fixture','csrf_token'=>$token][$key]??$default;}
 function getObject($name,$module){
  if($name==='shorturlsecurity')return new shorturlsecurity();
  if($name==='language')return new class {function languageText($key,$module){return $key;}};
  return parent::getObject($name,$module);
 }
 function setLayoutTemplate($name){}
}
require __DIR__.'/../controller.php';
$c=new shorturl();
$p=new ReflectionProperty(shorturl::class,'objMap');
$p->setValue($c,new class {function deleteMapping($id){controller::$writes++;}});
register_shutdown_function(static function(){echo 'RESULT:'.json_encode(['status'=>http_response_code(),'writes'=>controller::$writes]);});
$c->dispatch();
