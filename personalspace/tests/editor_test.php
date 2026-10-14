<?php
/** Standalone request-boundary tests; no database or real account writes. */
$GLOBALS['kewl_entry_point_run']=true;
set_error_handler(static function($n,$s){throw new ErrorException($s,0,$n);});
class ChisimbaObject {public static array $objects=[];function getObject($name,$module=''){return self::$objects[$name];}}
require dirname(__DIR__).'/classes/personalspaceeditor_class_inc.php';
$objects=&ChisimbaObject::$objects;
$objects['user']=new class{public bool $logged=true;function isLoggedIn(){return $this->logged;}function userId(){return 'owner';}};
$csrf=new class{public array $purposes=[];function consume($purpose,$token){$this->purposes[]=$purpose;return $token==='valid';}function issue($purpose){return 'renewed';}};
$objects['nativeauthwebcomposition']=new class($csrf){function __construct(private $csrf){}function build(){return ['csrf'=>$this->csrf];}};
$objects['language']=new class{function languageText($key,$module){return $key;}};
$objects['dbpersonalspaceblocks']=new class{public array $writes=[];function editPlacement(...$args){$this->writes[]=$args;if($args[3]==='foreign-placement')throw new RuntimeException('block_denied');return 'new-revision';}};
$objects['dynamicblocks']=new class{function getSmallUserBlocks($owner){if($owner!=='owner')throw new LogicException('wrong owner');return [['id'=>'own','module'=>'fixture']];}function getWideUserBlocks($owner){return [['id'=>'wide-own','module'=>'fixture']];}function showBlock($id){return 'Scoped dynamic block';}};
$objects['dbmoduleblocks']=new class{function getBlocks($width,$type){if($type!=='site|user')throw new LogicException('wrong scope');return [['blockname'=>'sample','moduleid'=>'fixture']];}};
$objects['modules']=new class{public bool $enabled=true;function checkIfRegistered($module){return $module==='fixture' && $this->enabled;}};
$objects['blocks']=new class{function showBlock(...$args){return 'Allowed block';}};
$editor=new personalspaceeditor();$input=['csrf_token'=>'valid','revision'=>'original','blockid'=>'block|sample|fixture','side'=>'middle','direction'=>'up','userid'=>'victim'];
function check($ok,$message){if(!$ok)throw new RuntimeException($message);}
foreach(['addblock','removeblock','moveblock','renderblock'] as $action)check($editor->handle($action,$input,'GET')['status']===405,'GET denied');
foreach([null,[], 'expired'] as $token){$bad=$input;$bad['csrf_token']=$token;check($editor->handle('addblock',$bad,'POST')['code']==='block_csrf','CSRF rejected');}
$objects['user']->logged=false;check(!$editor->handle('addblock',$input,'POST')['ok'],'anonymous denied');$objects['user']->logged=true;
foreach(['block|arbitrary|security','dynamicblock|foreign|fixture','dynamicblock|own|different'] as $id){$bad=$input;$bad['blockid']=$id;check(!$editor->handle('renderblock',$bad,'POST')['ok'],'unoffered/foreign block denied');}
foreach(['bad',[]] as $side){$bad=$input;$bad['side']=$side;check(!$editor->handle('addblock',$bad,'POST')['ok'],'invalid side denied');}
$bad=$input;$bad['direction']='arbitrary';check(!$editor->handle('moveblock',$bad,'POST')['ok'],'direction denied');
check($objects['dbpersonalspaceblocks']->writes===[],'negative paths never persist');
$bad=$input;$bad['blockid']='dynamicblock|own|fixture';$bad['side']='left';check($editor->handle('renderblock',$bad,'POST')['ok'],'own small preview');
$bad['side']='middle';check(!$editor->handle('renderblock',$bad,'POST')['ok'],'wrong-size dynamic denied');
$bad['blockid']='dynamicblock|wide-own|fixture';check($editor->handle('renderblock',$bad,'POST')['ok'],'own wide preview');
$objects['modules']->enabled=false;check(!$editor->handle('renderblock',$bad,'POST')['ok'],'revoked module denied');$objects['modules']->enabled=true;
check($editor->handle('addblock',$input,'POST')['revision']==='new-revision','valid add');
check($objects['dbpersonalspaceblocks']->writes[0][0]==='owner','submitted victim ignored');
check(count(array_unique($csrf->purposes))===1 && $csrf->purposes[0]===$editor->purpose('owner'),'tokens bound to authenticated owner');
$bad=$input;$bad['blockid']='foreign-placement';check($editor->handle('removeblock',$bad,'POST')['code']==='block_denied','repository ownership denial retained');
echo "PASS Personal Space owner scope, POST/CSRF, allowed blocks, dynamic ownership/size and revoked module\n";
