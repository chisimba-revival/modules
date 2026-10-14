<?php
/** Run with PHP; no database or installed site required. */
error_reporting(E_ALL);
set_error_handler(function ($severity,$message,$file,$line) { throw new ErrorException($message,0,$severity,$file,$line); });
$GLOBALS['kewl_entry_point_run']=true;
class ChisimbaObject {}
class controller extends ChisimbaObject {
    public $params=[],$vars=[],$objects=[];
    public function getParam($key,$default=null) { return $this->params[$key]??$default; }
    public function getObject($name,$module) { return $this->objects[$name]; }
    public function setVar($key,$value) { $this->vars[$key]=$value; }
    public function nextAction($action,$params=[]) { return 'redirect:'.$action; }
}
require __DIR__.'/../classes/tripleadmin_class_inc.php';
require __DIR__.'/../controller.php';
function check($condition,$message) { if (!$condition) throw new RuntimeException($message); }
class FakeUser { public $logged=true,$admin=true; function isLoggedIn(){return $this->logged;} function isAdmin(){return $this->admin;} }
class FakeStore {
    public $row=['id'=>'fixture','subject'=>'mentor','predicate'=>'isMentorOf','object'=>'learner','userid'=>'creator','date'=>'2026-01-01'];
    public $writes=0;
    function transaction($fn){return $fn();}
    function lockedTriple($id){return $id==='fixture'?$this->row:null;}
    function update($id,$s,$p,$o){$this->writes++;$this->row=array_merge($this->row,['subject'=>$s,'predicate'=>$p,'object'=>$o]);return true;}
    function delete($id){$this->writes++;return true;}
    function insert($s,$p,$o){$this->writes++;return 'new';}
    function getTriples($filters){return $filters['id']==='fixture'?[$this->row]:[];}
    function browse($filters,$page){return ['rows'=>[$this->row],'total'=>1,'page'=>1,'size'=>30];}
}
function inject($object,$property,$value) {(new ReflectionProperty($object,$property))->setValue($object,$value);}
$user=new FakeUser();$store=new FakeStore();$admin=new tripleadmin();inject($admin,'user',$user);inject($admin,'store',$store);
$values=['subject'=>'mentor','predicate'=>'isMentorOf','object'=>'learner'];$rev=tripleadmin::revision($store->row);
$user->admin=false;check($admin->write('fixture',$values,$rev)==='forbidden','non-admin write');
$user->admin=true;$user->logged=false;check(!$admin->mayManage(),'anonymous admin flag');$user->logged=true;
check($admin->write('fixture',$values,'old')==='conflict' && $store->writes===0,'stale write');
check($admin->write('fixture',[],'old',true)==='conflict' && $store->writes===0,'stale delete');
check($admin->write('missing',$values,$rev)==='missing','missing record');
check(!tripleadmin::validValues(array_merge($values,['subject'=>str_repeat('x',33)])),'length');
check(!tripleadmin::validValues(array_merge($values,['object'=>['array']])),'array input');
check(tripleadmin::validValues(array_merge($values,['object'=>'0'])),'zero value');
check($admin->write('fixture',array_merge($values,['object'=>'updated']),$rev)==='saved','valid update');
check($store->row['userid']==='creator' && $store->row['date']==='2026-01-01','creation metadata');
$csrf=new class {public $valid=false;function consume($scope,$token){return $this->valid;}function issueForSession($scope){return 'fresh';}};
$web=new triplestore();$web->objects=['language'=>new stdClass(),'tripleadmin'=>$admin,'dbtriplestore'=>$store,'nativeauthwebcomposition'=>new class($csrf){function __construct(private $csrf){}function build(){return ['csrf'=>$this->csrf];}}];$web->init();
$user->admin=false;foreach(['home','view','edit','save','delete','upload'] as $action) check($web->dispatch($action)==='message_tpl.php' && http_response_code()===403,'route access: '.$action);$user->admin=true;
$_SERVER['REQUEST_METHOD']='GET';$before=$store->writes;check($web->dispatch('delete')==='message_tpl.php' && http_response_code()===405 && $store->writes===$before,'GET delete');
$_SERVER['REQUEST_METHOD']='POST';$web->params=$values+['id'=>'fixture','revision'=>$rev,'csrf_token'=>'expired'];
check($web->dispatch('save')==='editform_tpl.php' && $web->vars['notice']==='expired' && $web->vars['triple']['object']==='learner' && $web->vars['token']==='fresh' && $store->writes===$before,'expired form retains work');
$csrf->valid=true;$web->dispatch('save');check($web->vars['notice']==='conflict','controller conflict');
check($web->dispatch('upload')==='message_tpl.php' && http_response_code()===410,'old upload retired');
check($web->requiresLogin('view')===true,'JSON requires login');
foreach (['../resources/triplestore.js','../classes/tripleui_class_inc.php'] as $path) check(!file_exists(__DIR__.'/'.$path),'obsolete vendor helper retained');
$web->params=['format'=>'nested'];ob_start();$web->dispatch('view');$json=ob_get_clean();
check(json_decode($json,true)['mentor']['isMentorOf']['fixture']==='updated','protected nested JSON');
$web->params=['format'=>'flat'];ob_start();$web->dispatch('view');$json=ob_get_clean();
check(json_decode($json,true)[0]['id']==='fixture','protected flat JSON');
echo "PASS: administrator policy, stale writes/deletes, validation, metadata, POST/CSRF guards, retained entries and retired routes.\n";
