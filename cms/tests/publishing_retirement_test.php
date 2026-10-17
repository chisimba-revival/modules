<?php
/** Exercise real retirement dispatch without booting dormant module services. */
error_reporting(E_ALL);
set_error_handler(static function($s,$m,$f,$l){throw new ErrorException($m,0,$s,$f,$l);});
$GLOBALS['kewl_entry_point_run']=true;
class controller {
 public $objDbGal;
 public $params=[];
 public $vars=[];
 public function getParam($name,$default=null){return $this->params[$name]??$default;}
 public function getObject(...$args){throw new RuntimeException('Retired endpoint loaded a service');}
 public function setPageTemplate($value){}
 public function setLayoutTemplate($value){}
 public function setVar($key,$value){$this->vars[$key]=$value;}
 public function setVarByRef($key,&$value){$this->vars[$key]=$value;}
}
$modules=dirname(__DIR__,2);
foreach(['cms'=>'serverpc','simplegal'=>'metaweblog'] as $name=>$action){
 $directory = $name === 'simplegal' ? 'simplegal__PAUSED' : $name;
 require $modules.'/'.$directory.'/controller.php';
 foreach(['GET','POST'] as $method){
  $_SERVER['REQUEST_METHOD']=$method;
  foreach([$action,strtoupper($action)] as $variant){
   $module=new $name();$module->params=['action'=>$variant];
   $module->init();http_response_code(200);
   if($module->dispatch()!=='publishing_retired_tpl.php'||http_response_code()!==410)throw new RuntimeException('Retirement failed');
  }
 }
 $source=file_get_contents($modules.'/'.$directory.'/controller.php');
 if(str_contains($source,'getObject(\'cmsrpcapi\'')||str_contains($source,'getObject(\'simplegalxmlrpcapi\''))throw new RuntimeException('Eager RPC loader returned');
}
$gallery=new simplegal();$gallery->objDbGal=new class {
 public function retrieveData($user){if($user!==1)throw new RuntimeException('Gallery user changed');return ['synthetic gallery row'];}
};
if($gallery->dispatch()!=='view_tpl.php'||$gallery->vars['data']!==['synthetic gallery row'])throw new RuntimeException('Normal gallery dispatch changed');
foreach(json_decode(file_get_contents(__DIR__.'/retired-publishing-paths.json'),true) as $path){
 $path=preg_replace('~^simplegal/~','simplegal__PAUSED/',$path);
 if(file_exists($modules.'/'.$path))throw new RuntimeException('Retired publishing class returned');
}
if(class_exists('XML_RPC_Server',false))throw new RuntimeException('RPC loaded');
echo "PASS retired publishing GET/POST and case variants return 410 without services; normal gallery dispatch and data preserved\n";
