<?php
if (PHP_SAPI !== 'cli') exit(64);
ob_start(); require __DIR__.'/security_test.php'; ob_end_clean();
[$script,$role,$method,$token]=$argv;
ChisimbaObject::$role=$role;
$_SERVER['REQUEST_METHOD']=$method;
http_response_code(200);
class controller extends ChisimbaObject {
 public static int $writes=0;
 function getParam($key,$default=null){global $token;return ['action'=>'delete','id'=>'fixture','confirm'=>'yes','csrf_token'=>$token,'revision'=>str_repeat('a',64)][$key]??$default;}
 function getObject($name,$module){
  if($name==='shorturlsecurity')return new shorturlsecurity();
  if($name==='language')return new class {function languageText($key,$module){return $key;}};
  return parent::getObject($name,$module);
 }
 function setLayoutTemplate($name){}
 function nextAction($name){return null;}
 function setVar($name,$value){}
 function getJavaScriptFile($file,$module){return "";}
 function appendArrayVar($name,$value){}
}
require __DIR__.'/../controller.php';
$c=new shorturl();
$c->policy=new shorturlsecurity();
$c->repository=new class {function deleteMapping($id,$revision){controller::$writes++;} function listMappings(){return [];}};
$c->language=$c->getObject('language','language');
register_shutdown_function(static function(){echo 'RESULT:'.json_encode(['status'=>http_response_code(),'writes'=>controller::$writes]);});
$c->dispatch();
