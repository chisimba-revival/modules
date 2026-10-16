<?php
if (PHP_SAPI !== 'cli') exit(64);
error_reporting(E_ALL);
set_error_handler(static function($n,$s){throw new RuntimeException($s);});
$GLOBALS['kewl_entry_point_run'] = true;
class ChisimbaObject {
 public static string $role='admin';
 public function getObject($name,$module) {
  if ($name==='user') return new class {
   function isLoggedIn(){return ChisimbaObject::$role!=='anonymous';}
   function userId(){return 'fixture';}
   function lookupAdmin($id){return ChisimbaObject::$role==='admin';}
  };
  if ($name==='nativeauthwebcomposition') return new class {
   function build(){return ['csrf'=>new class {
    function consume($scope,$token){return $scope==='shorturl_edit' && $token==='valid';}
    function issue($scope){return 'fixture-token';}
   }];}
  };
  throw new RuntimeException('Unexpected service');
 }
}
require __DIR__.'/../classes/shorturlsecurity_class_inc.php';
$s=new shorturlsecurity();
$base=['id'=>'fixture','confirm'=>'yes','csrf_token'=>'valid','revision'=>str_repeat('a',64),'txtMatchUrl'=>'bird','txtTargetUrl'=>'https://example.test/bird','ordering'=>'0'];
function rejects($s,$action,$input,$method,$error) {
 try{$s->check($action,$input,$method);}catch(DomainException $e){if($e->getMessage()===$error)return;throw $e;}
 throw new RuntimeException('Accepted unsafe request: '.$error);
}
foreach(['anonymous','member','revoked-admin'] as $role){
 ChisimbaObject::$role=$role;
 foreach(['list','edit','save','delete'] as $action) rejects($s,$action,$base,'POST','denied');
 $s->check('redirect',[],'GET');
}
ChisimbaObject::$role='admin';
foreach(['save','delete'] as $action){
 rejects($s,$action,$base,'GET','method');
 rejects($s,$action,array_replace($base,['csrf_token'=>'expired']),'POST','csrf');
 $s->check($action,$base,'POST');
}
foreach(['serverpc','releaselock','getMethod','unknown'] as $action)rejects($s,$action,$base,'POST','invalid');
foreach([['id'=>"x' OR 1=1"],['id'=>[]]] as $change)rejects($s,'save',array_replace($base,$change),'POST','invalid');
rejects($s,'save',array_replace($base,['revision'=>'']),'POST','stale');
if(!str_contains($s->tokenField(),'name="csrf_token"'))throw new RuntimeException('Missing form token');
echo "PASS current roles, public redirect, explicit actions, methods, CSRF, scalar IDs and stale revision shape\n";
