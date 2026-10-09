<?php
/** Public expansion and stable, manager-only link retrieval. @author Derek Keats */
$GLOBALS['kewl_entry_point_run'] = true;
class controller {
    public array $vars = array();
    public function setVar($key, $value) {$this->vars[$key] = $value;}
    public function getParam($key, $default = '') {return $key === 'mapid' ? str_repeat('a', 32) : $default;}
    public function uri($params, $module = '') {return '/index.php?module=knowledgemap&amp;'.http_build_query($params, '', '&amp;');}
}
require __DIR__.'/../controller.php';
require __DIR__.'/../classes/knowledgemapservice_class_inc.php';
function inject($object, $name, $value) {(new ReflectionProperty($object, $name))->setValue($object, $value);}
function check($condition, $message) {if (!$condition) throw new RuntimeException($message); echo "PASS: $message\n";}
$map = array('id'=>str_repeat('a',32),'title'=>'Grasses fixture','description'=>'','scopetype'=>'personal','scopeid'=>'','revision'=>7,'rootnodeid'=>'root');
$rows = array();
foreach (array('root'=>'{"collapsed":true,"color":"green"}', 'child'=>'{"collapsed":true}', 'leaf'=>'null') as $id=>$presentation) {
    $rows[] = array('id'=>$id,'nodetype'=>'standard','title'=>$id,'description'=>'','presentation'=>$presentation,'sortorder'=>0);
}
$service = new knowledgemapservice();
$nodes = new class($rows) {public function __construct(public array $rows) {} public function forMap($id) {return $this->rows;}};
$auth = new class {public bool $manage=true; public function allows($map,$permission) {return $permission!=='manage'||$this->manage;}};
inject($service,'maps',new class($map) {public function __construct(public array $map) {} public function one($id) {return $this->map;}});
inject($service,'nodes',$nodes);
inject($service,'relationships',new class {public function forMap($id) {return array();}});
inject($service,'authorization',$auth);
$public = $service->publicDocument($map);
check(count(array_filter($public['nodes'],fn($node)=>$node['presentation']['collapsed']===false))===3,'public view expands every node, including null presentation');
check($public['nodes'][0]['presentation']['color']==='green','public expansion preserves other presentation');
check($nodes->rows===$rows && $service->document($map['id'])['nodes'][0]['presentation']['collapsed']===true,'saved nodes and authenticated layout remain unchanged');
$access = new class {public $token; public int $reads=0; public function publicLinkToken($id) {$this->reads++;return $this->token;} public function grants($id) {return array();}};
$access->token=str_repeat('b',64);
$page = new knowledgemap();
inject($page,'service',$service);inject($page,'authorization',$auth);inject($page,'access',$access);
inject($page,'csrf',new class {public function issue($scope) {return 'test-csrf';}});
$page->dispatch('view');$first=$page->vars['knowledgeMapPublicUrl'];
$page->dispatch('view');
check($first===$page->vars['knowledgeMapPublicUrl'] && str_contains($first,'token='.$access->token) && !str_contains($first,'&amp;'),'reopening retrieves the same usable public URL without replacing the grant');
$auth->manage=false;$reads=$access->reads;$page->dispatch('view');
check($page->vars['knowledgeMapPublicUrl']===null && !$page->vars['knowledgeMapPublicLinkActive'] && $access->reads===$reads,'non-managers cannot retrieve the public token');
$auth->manage=true;$access->token=false;$page->dispatch('view');
check($page->vars['knowledgeMapPublicUrl']===null && !$page->vars['knowledgeMapPublicLinkActive'],'revoked or absent links stay absent');
