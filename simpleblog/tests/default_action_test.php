<?php
/** Public default routes agree with the login gate; mutation routes stay protected. */
$GLOBALS['kewl_entry_point_run']=true;
class controller {
 public $params=[],$objects=[],$vars=[];
 public function getParam($key,$default=''){return $this->params[$key]??$default;}
 public function getObject($name,$module=''){return $this->objects[$name];}
 public function setVar($name,$value){$this->vars[$name]=$value;}
 public function uri($args,$module){return '/index.php?module='.$module;}
}
class publishingrenderer {
 public static function escape($text){return htmlspecialchars($text,ENT_QUOTES,'UTF-8');}
 public function identity(...$args){return 'Blog';}
 public function text($key){return $key;}
}
require $argv[1]??dirname(__DIR__).'/controller.php';
$c=new simpleblog();$c->objects=['publishingservice'=>new stdClass(),'publishingpolicy'=>new class {public function validScope(...$args){return true;}public function canRead(...$args){return true;}},'user'=>new stdClass(),'nativeauthwebcomposition'=>new class {public function build(){return ['csrf'=>new stdClass()];}},'publishingrenderer'=>new publishingrenderer(),'tools'=>new class {public function replaceBreadCrumbs($items){}}];$c->init();
foreach([[],['action'=>''],['action'=>'  '],['action'=>'view']] as $params){$c->params=$params;if($c->requiresLogin()||$c->dispatch()!=='posts_tpl.php')throw new RuntimeException('Public default failed');}
foreach(['save','delete','edit','manage','unknown',['view']] as $action){$c->params=['action'=>$action];if(!$c->requiresLogin())throw new RuntimeException('Protected action became public');}
$c->params=['action'=>'unknown'];if($c->dispatch()!=='message_tpl.php')throw new RuntimeException('Unknown route accepted');
echo "PASS: missing/empty/whitespace/view list publicly; protected and malformed actions require login; unknown route rejected.\n";
