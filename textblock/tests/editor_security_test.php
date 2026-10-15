<?php
/** Standalone behavioural checks for both administrator editor boundaries. */
if (PHP_SAPI !== 'cli') exit(64);
$GLOBALS['kewl_entry_point_run'] = true;
set_error_handler(static function ($n,$s) { throw new ErrorException($s,0,$n); });
class ChisimbaObject { public static array $objects=[]; public function getObject($name,$module='') { return self::$objects[$name]; } }
require dirname(__DIR__).'/classes/textblocksecurity_class_inc.php';
require dirname(__DIR__,2).'/slate/classes/slatesecurity_class_inc.php';
$o = &ChisimbaObject::$objects;
$o['user'] = new class { public bool $logged=true,$admin=true; function isLoggedIn(){return $this->logged;} function userId(){return 'fixture';}function lookupAdmin($id){return $this->admin;}function isAdmin(){return true;} };
$csrf = new class { function consume($context,$token){return $token === $context.'-valid';} };
$o['nativeauthwebcomposition'] = new class($csrf) { function __construct(private $csrf){} function build(){return ['csrf'=>$this->csrf];} };
$o['dbtextblock'] = new class { function getBlockById($id){return $id==='existing'?[['id'=>$id]]:[];} };
$o['dbslatepages'] = new class { function requirePageKey($key){if($key!==str_repeat('a',32))throw new DomainException('editor_missing');} function getPageById($id){return $id==='existing'?[['id'=>$id,'page'=>'1']]:[];} function selectPrepared($sql,$values){return $values===['placement',str_repeat('a',32)]?[['id'=>'placement']]:[];} };
$o['dbmoduleblocks'] = new class { function getBlocks($width,$type){return [['moduleid'=>'textblock','blockname'=>'text1']];} };
$o['dynamicblocks'] = new class { function getWideSiteBlocks(){return [];} function getSmallSiteBlocks(){return [['id'=>'site','module'=>'fixture']];} };
$o['modules'] = new class { function checkIfRegistered($module){return in_array($module,['textblock','fixture'],true);} };
function check($ok,$message){if(!$ok)throw new RuntimeException($message);}
function denied($call,$expected){try{$call();}catch(DomainException $e){check($e->getMessage()===$expected,'Expected '.$expected.', got '.$e->getMessage());return;}throw new RuntimeException('Request unexpectedly permitted');}
$t=new textblocksecurity(); $s=new slatesecurity();
$text=['csrf_token'=>'textblock_edit-valid','id'=>'','mode'=>'add','blockid'=>'text1','title'=>'Title','blocktext'=>'<p>Draft</p>','css_id'=>'','css_class'=>'','show_title'=>'on'];
$page=['csrf_token'=>'slate_edit-valid','id'=>'','mode'=>'add','page'=>'1','title'=>'Title','description'=>'Description'];
foreach ([[$t,'save',$text],[$s,'pagesave',$page]] as [$service,$action,$input]) {
 $service->check($action,$input,'POST');
 denied(fn()=>$service->check($action,$input,'GET'),'editor_method');
 foreach ([null,[], 'invalid'] as $token) { $bad=$input;$bad['csrf_token']=$token;denied(fn()=>$service->check($action,$bad,'POST'),'editor_csrf'); }
 $o['user']->logged=false;denied(fn()=>$service->check($action,$input,'POST'),'editor_denied');$o['user']->logged=true;
 $o['user']->admin=false;denied(fn()=>$service->check($action,$input,'POST'),'editor_denied');$o['user']->admin=true;
 foreach (['',[],str_repeat('x',251)] as $title) {$bad=$input;$bad['title']=$title;denied(fn()=>$service->check($action,$bad,'POST'),'editor_invalid');}
 $bad=$input;$bad['id']='missing';$bad['mode']='edit';denied(fn()=>$service->check($action,$bad,'POST'),'editor_missing');
}
foreach (['text0','widetext61',"text1' OR 1=1",'text%'] as $slot) {$bad=$text;$bad['blockid']=$slot;denied(fn()=>$t->check('save',$bad,'POST'),'editor_invalid');}
foreach (['css_id','css_class'] as $key) {$bad=$text;$bad[$key]='" onmouseover="alert(1)';denied(fn()=>$t->check('save',$bad,'POST'),'editor_invalid');}
$bad=$page;$bad['id']='existing';$bad['mode']='edit';$bad['page']='2';denied(fn()=>$s->check('pagesave',$bad,'POST'),'editor_invalid');
foreach (['__getMethod','getMethod','validAction','unknown',[]] as $action) denied(fn()=>$s->check($action,[],'GET'),'editor_invalid');
$block=['csrf_token'=>'slate_edit-valid','blockid'=>'block|text1|textblock','side'=>'left','pageId'=>str_repeat('a',32),'direction'=>'up'];
$s->check('addblock',$block,'POST');$s->check('renderblock',$block,'POST');
$bad=$block;$bad['blockid']='dynamicblock|site|fixture';$s->check('renderblock',$bad,'POST');
foreach (['block|arbitrary|security','dynamicblock|private|fixture'] as $id) {$bad=$block;$bad['blockid']=$id;denied(fn()=>$s->check('renderblock',$bad,'POST'),'editor_denied');}
$block['blockid']='placement';$s->check('moveblock',$block,'POST');$s->check('removeblock',$block,'POST');
$block['pageId']=str_repeat('b',32);denied(fn()=>$s->check('moveblock',$block,'POST'),'editor_missing');
$o['user']->admin=false;
foreach (['manage','edit','gettaken','delete','renderblock','addblock','removeblock','moveblock'] as $action) denied(fn()=>$s->check($action,$block,'POST'),'editor_denied');
$s->check('view',['page'=>'1'],'GET');
echo "PASS: admin/current role, methods, tokens, malformed fields, action allowlist, block catalogue and page scope\n";
