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
    function consume($scope,$token){return $scope==='cms_user_permissions' && $token==='valid';}
    function issue($scope){return 'fixture-token';}
   }];}
  };
  throw new RuntimeException('Unexpected service');
 }
}
require __DIR__.'/../classes/permissionrequest_class_inc.php';
$s=new permissionrequest();
$base=['id'=>'record-one','drp_owner'=>'user-two','chk_frontpage'=>'on','csrf_token'=>'valid','confirm'=>'yes'];
function rejectPermission($s,$action,$input,$method,$reason,$type='') {
 try{$s->check($action,$type,$input,$method);}catch(DomainException $e){if($e->getMessage()===$reason)return;throw $e;}
 throw new RuntimeException('Unsafe request accepted: '.$reason);
}
foreach(['anonymous','member','cms-author','revoked-admin'] as $role) {
 ChisimbaObject::$role=$role;
 foreach(['permissionsuser','addedituserpermissions','deleteuserperm'] as $action) rejectPermission($s,$action,$base,'POST','denied');
 rejectPermission($s,'ajaxforms',$base,'GET','denied','showuserpermaddform');
}
ChisimbaObject::$role='admin';
foreach(['addedituserpermissions','deleteuserperm'] as $action) {
 rejectPermission($s,$action,$base,'GET','method');
 rejectPermission($s,$action,array_replace($base,['csrf_token'=>'expired']),'POST','csrf');
 $s->check($action,'',$base,'POST');
}
foreach([['id'=>[]],['id'=>"x' OR 1=1"],['drp_owner'=>[]],['chk_frontpage'=>'forged']] as $change) rejectPermission($s,'addedituserpermissions',array_replace($base,$change),'POST','invalid');
rejectPermission($s,'deleteuserperm',array_replace($base,['confirm'=>'']),'POST','invalid');
if (!str_contains($s->tokenField(),'name="csrf_token"')) throw new RuntimeException('No token field');
echo "PASS permission request: current administrator, author/revoked denial, POST/CSRF and scalar input
";
