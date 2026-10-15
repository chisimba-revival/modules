<?php
/** Subprocess fixture: real controllers and policies, synthetic dependencies only. */
if (PHP_SAPI !== 'cli') exit(64);
$GLOBALS['kewl_entry_point_run']=true;
$module=$argv[1];$action=$argv[2];$role=$argv[3];$method=$argv[4];$token=$argv[5];
$writes=0;
set_error_handler(static function($n,$s){throw new ErrorException($s,0,$n);});
class ChisimbaObject {
 public static array $objects=[];
 public function getObject($name,$module=null){return self::$objects[$name];}
 public function getParam($key,$default=null){global $action,$token;return ['action'=>$action,'id'=>'existing','mode'=>'edit','page'=>'1','title'=>'Title','description'=>'Description','blockid'=>'text1','csrf_token'=>$token][$key] ?? $default;}
}
class controller extends ChisimbaObject { public $objUser; function appendArrayVar(...$a){} }
$user=new class($role) {function __construct(private $role){}function isLoggedIn(){return $this->role!=='anonymous';}function userId(){return 'fixture';}function lookupAdmin($id){return $this->isAdmin();}function isAdmin(){return $this->role==='admin';}};
$repo=new class {function getBlockById($id){return [['id'=>$id]];}function getPageById($id){return [['id'=>$id,'page'=>'1']];}function deleteRecord(...$a){global $writes;$writes++;return true;}function deletePage(...$a){global $writes;$writes++;return true;}};
$csrf=new class {function consume($context,$token){return $token==='valid';}function issue($context){return 'synthetic';}};
ChisimbaObject::$objects=['user'=>$user,'dbtextblock'=>$repo,'dbslatepages'=>$repo,'language'=>new class{function languageText($key,$module=null){return $key;}},'nativeauthwebcomposition'=>new class($csrf){function __construct(private $csrf){}function build(){return ['csrf'=>$this->csrf];}}];
require dirname(__DIR__,2).'/'.$module.'/classes/'.$module.'security_class_inc.php';
require dirname(__DIR__,2).'/'.$module.'/controller.php';
$class=$module.'security';ChisimbaObject::$objects[$class]=new $class();
$c=new $module();$c->objUser=$user;
if($module==='textblock'){$c->action=$action;$c->objDb=$repo;$c->objLanguage=ChisimbaObject::$objects['language'];}
$_SERVER['REQUEST_METHOD']=$method;http_response_code(200);
register_shutdown_function(function()use(&$writes){echo "\nRESULT:".json_encode(['status'=>http_response_code(),'writes'=>$writes]);});
$c->dispatch();
