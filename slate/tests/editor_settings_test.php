<?php
$GLOBALS['kewl_entry_point_run']=true;
set_error_handler(static function($n,$s){throw new ErrorException($s,0,$n);});
class ChisimbaObject {public static array $objects=[];function getObject($name,$module=''){return self::$objects[$name];}}
require dirname(__DIR__).'/classes/slatesecurity_class_inc.php';
require dirname(__DIR__).'/classes/slateblockeditor_class_inc.php';
$o=&ChisimbaObject::$objects;
$o['user']=new class{public bool $logged=false,$current=false;function isLoggedIn(){return $this->logged;}function userId(){return 'fixture';}function lookupAdmin($id){return $this->current;}function isAdmin(){return true;}};
$csrf=new class{public int $issued=0;function issue($purpose){++$this->issued;return 'fixture-token';}};
$o['nativeauthwebcomposition']=new class($csrf){function __construct(private $csrf){}function build(){return ['csrf'=>$this->csrf];}};
$o['language']=new class{function languageText(...$args){return 'Label';}};
$o['slatesecurity']=new slatesecurity();$editor=new slateblockeditor();
function check($ok,$message){if(!$ok)throw new RuntimeException($message);}
check($editor->settings('page','revision')==='','anonymous gets no editor');
$o['user']->logged=true;check($editor->settings('page','revision')==='','cached but revoked administrator gets no editor');
check($csrf->issued===0,'no editing tokens issued without persisted access');
$o['user']->current=true;$html=$editor->settings('page','revision');
check(str_contains($html,'data-module="slate"')&&str_contains($html,'fixture-token'),'current administrator gets native editor');
echo "PASS Slate anonymous, revoked/cached administrator and current administrator editor settings\n";
