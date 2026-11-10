<?php
ob_start();$GLOBALS['kewl_entry_point_run']=true;
class controller {public function getParam($key,$default=null){return $key==='action'?'redirect':$default;}public function getObject($name,$module){return new class{function getItem($key){return 'https://example.test/';}};}}
class dbTable{}
require dirname(__DIR__).'/classes/dbmap_class_inc.php';require dirname(__DIR__).'/controller.php';
$c=new shorturl();$c->policy=new class{function check(...$args){}function requireAdmin(){throw new RuntimeException('Public path entered administration');}};$c->language=new class{function languageText(...$args){return 'Not found';}};
$c->repository=new class {public $paths=[];function getTarget($p){$this->paths[]=$p;return $p==='go/bird/'?'https://example.test/index.php?module=sitepages&action=view&id=stable':null;}};
foreach(['/go/bird/','/go/bird','/go/bird/?utm_source=book'] as $uri){$_SERVER['REQUEST_URI']=$uri;http_response_code(200);$c->dispatch();if(http_response_code()!==302)throw new RuntimeException('Reusable link failed');}
$_SERVER['REQUEST_URI']='/missing/';$c->dispatch();if(http_response_code()!==404)throw new RuntimeException('Unknown link did not fail closed');ob_end_clean();echo "PASS public redirects, trailing slash, tracking query, missing-path404\n";
