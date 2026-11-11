<?php
$GLOBALS['kewl_entry_point_run']=true;
class ChisimbaObject {
    public static $role='member', $course='field', $exists=true;
    public function getObject($name,$module) {
        return match($name) {
            'user'=>new class {
                function isLoggedIn(){return ChisimbaObject::$role!=='anonymous';}
                function userId(){return 'fixture';}
                function lookupAdmin($id){return ChisimbaObject::$role==='admin';}
                function isContextLecturer($id,$course){return ChisimbaObject::$role==='teacher';}
            },
            'dbcontext'=>new class { function getContextCode(){return ChisimbaObject::$course;} function getContext($c){return ChisimbaObject::$exists?['contextcode'=>$c]:false;} },
            'usercontext'=>new class {function isContextMember($u,$c){return in_array(ChisimbaObject::$role,['member','teacher']);}},
            'nativeauthwebcomposition'=>new class {function build(){return ['csrf'=>new class {function consume($s,$t){return $s==='sightsandsounds'&&$t==='valid';}}];}},
            default=>throw new RuntimeException('Unexpected service '.$name)
        };
    }
}
require __DIR__.'/../classes/practiceaccess_class_inc.php';
$a=new practiceaccess();
foreach(['anonymous','outsider','revoked'] as $role) {
    ChisimbaObject::$role=$role;
    try{$a->scope();throw new RuntimeException('Denied role accepted');}catch(DomainException $e){}
    if($a->mayStoreFiles())throw new RuntimeException('Denied upload accepted');
}
ChisimbaObject::$role='member'; if($a->manager())throw new RuntimeException('Member can author');
foreach(['teacher','admin'] as $role){ChisimbaObject::$role=$role;if(!$a->manager())throw new RuntimeException('Author denied');}
foreach([['GET','valid'],['POST','expired']] as [$method,$token]) {
    try{$a->mutation($method,$token);throw new RuntimeException('Unsafe mutation');}catch(DomainException $e){}
}
$a->mutation('POST','valid');
ChisimbaObject::$course='root';if($a->mayStoreFiles())throw new RuntimeException('Root bank allowed');
echo "PASS current course membership, revoked users, author roles, POST and CSRF\n";
