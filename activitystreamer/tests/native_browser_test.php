<?php
/** Isolated policy/rendering tests, no installed site required. */
error_reporting(E_ALL);set_error_handler(function($n,$m,$f,$l){throw new ErrorException($m,0,$n,$f,$l);});
$GLOBALS['kewl_entry_point_run']=true;
class ChisimbaObject {
 public $objects=[];
 function getObject($name,$module=null){if(!isset($this->objects[$name]))throw new RuntimeException('Unexpected dependency: '.$name);return $this->objects[$name];}
 function uri($params,$module=null){return '/index.php?'.http_build_query(['module'=>$module]+$params);}
}
class dbTable extends ChisimbaObject {
 public $queries=[];
 function init($t=null,$p=null,$e=null){}
 function selectPrepared($sql,$params=[]){$this->queries[]=[$sql,$params];return str_contains($sql,'COUNT(*)')?[['total'=>61]]:[];}
}
class controller extends ChisimbaObject {public $params=[],$vars=[];function getParam($n,$d=null){return $this->params[$n]??$d;}function setVar($n,$v){$this->vars[$n]=$v;}}
foreach(['activitybrowser','activityview','activityutilities','activityops'] as $class)require __DIR__.'/../classes/'.$class.'_class_inc.php';
require __DIR__.'/../controller.php';
function check($v,$label){if(!$v)throw new RuntimeException($label);}
$user=new class{public $logged=true,$admin=false;function isLoggedIn(){return $this->logged;}function isAdmin(){return $this->admin;}};
$context=new class{public $code='course-a';function getContextCode(){return $this->code;}};
$groups=new class{public $lecturer=false;function isContextLecturer(){return $this->lecturer;}};
$browser=new activitybrowser();$browser->objects=['user'=>$user,'dbcontext'=>$context,'managegroups'=>$groups];
foreach(['site','context','pages'] as $mode){check(!$browser->mayView($mode),'student denied '.$mode);try{$browser->browse($mode);throw new LogicException('unexpected access');}catch(RuntimeException $e){check($e->getMessage()==='Activity access denied','correct access failure');}}
check(!$browser->queries,'denied before querying');
$groups->lecturer=true;check($browser->mayView('pages')&&$browser->mayView('context')&&!$browser->mayView('site'),'instructor scope');
$context->code='root';check(!$browser->mayView('pages'),'root scope');$context->code='course-a';
$data=$browser->browse('pages',['q'=>"' OR 1=1 --",'sort'=>'bad SQL','page'=>99]);
check($data['page']===3,'clamp page');
[$sql,$params]=end($browser->queries);check(str_contains($sql,"p.original='Y'")&&str_contains($sql,'a.contextcode=?')&&$params===['course-a',"%' OR 1=1 --%"],'bound original-page scope');
check(str_contains($sql,'LIMIT 30 OFFSET 60')&&!str_contains($sql,'bad SQL'),'bounded sort/paging');
$groups->lecturer=false;check(!$browser->mayView('pages'),'revoked instructor');$user->admin=true;check($browser->mayView('site'),'admin');$user->logged=false;check(!$browser->mayView('site'),'anonymous admin flag');$user->logged=true;
$view=new activityview();$view->objects=['activitybrowser'=>$browser,'language'=>new class{function code2Txt($key,$module){return $key;}}];
$html=$view->table(['mode'=>'site','rows'=>[['id'=>'x','title'=>'<script>alert(1)</script>','description'=>'<img src=x onerror=x>','createdby'=>'<b>Person</b>','createdon'=>'today']]]);
check(!str_contains($html,'<script>')&&!str_contains($html,'<img ')&&str_contains($html,'&lt;script&gt;'),'escape stored HTML');
$user->admin=false;check($view->block('site')==='','private blocks return no content');
foreach(['latestsiteactivity','latestcontextactivity','browseactivities','popuphandler','browsecourseactivities','browsecourseactivitiesbutton'] as $name){require __DIR__.'/../classes/block_'.$name.'_class_inc.php';$class='block_'.$name;$block=new $class();$block->objects=['activityview'=>$view];$block->init();check($block->show()==='','private block '.$name);}
$user->admin=true;
foreach(['latestsiteactivity','latestcontextactivity','browseactivities','popuphandler','browsecourseactivities','browsecourseactivitiesbutton'] as $name){$class='block_'.$name;$block=new $class();$block->objects=['activityview'=>$view];$block->init();$html=$block->show();check(str_contains($html,'Browse')||str_contains($html,'browse'),'authorised block '.$name);check(!str_contains($html,'<script'),'native block '.$name);}
$user->admin=false;
$web=new activitystreamer();$web->objects=['activitybrowser'=>$browser];foreach(['home','showactivities','jsonlistactivities','jsoncourseactivities'] as $action)check($web->dispatch($action)==='activities_tpl.php'&&$web->vars['denied']&&http_response_code()===403,'private route '.$action);
$store=new class{public $calls=0;function insertPost($n){$this->calls++;return 'fixture';}};
$config=new class{public $enabled='false';function getValue($k,$m){return $this->enabled;}};
$ops=new activityops();$ops->objects=['activitydb'=>$store,'dbsysconfig'=>$config];$ops->init();check($ops->postmade(new stdClass())===false&&$store->calls===0,'recording off');$config->enabled='true';check($ops->postmade(new stdClass())==='fixture'&&$store->calls===1,'recording opt-in');check($ops->createFeeds(new stdClass())===false,'no external publishing');
$user->admin=true;$utilities=new activityutilities();$utilities->objects=['activitybrowser'=>$browser];
$json=json_decode($utilities->jsonListActivity(30,30),true);check($json['totalCount']===61&&$json['activities']===[],'JSON count and rows');
[$sql,$params]=end($browser->queries);check(str_contains($sql,'OFFSET 30'),'JSON paging');
$user->admin=false;try{$utilities->jsonListActivity();throw new LogicException('JSON allowed');}catch(RuntimeException $error){check($error->getMessage()==='Activity access denied','JSON denied');}
check(glob(__DIR__.'/../resources/*.js')===[],'ExtJS assets removed');
echo "PASS: anonymous/student/revoked/instructor/admin scopes, bound queries, paging, escaping, private blocks/routes, opt-in recording and removed vendor assets.\n";
