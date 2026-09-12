<?php
$GLOBALS['kewl_entry_point_run']=true;
class ChisimbaObject { public function getObject($name,$module=null){return $GLOBALS['objects'][$name];} }
function check($condition,$message){if(!$condition)throw new RuntimeException($message);}
require dirname(__DIR__).'/classes/publishingpolicy_class_inc.php';
require dirname(__DIR__).'/classes/publishingservice_class_inc.php';
$user=new class {public $logged=false,$admin=false,$author=false,$id='author1';public function isLoggedIn(){return $this->logged;}public function isAdmin(){return $this->admin;}public function isLecturer(){return $this->author;}public function userId(){return $this->id;}public function isContextLecturer($id,$code){return $code==='allowed'&&$this->author;}public function isCourseAdmin($code){return false;}};
$perms=new class {public $grants=[];public function areaIdForName($a,$b){return 1;}public function rightIdForArea($a,$r){return $r;}public function isGranted($u,$r){return in_array($r,$this->grants,true);}};
$GLOBALS['objects']=array('user'=>$user,'permissionservice'=>$perms,'dbcontext'=>new class{public function getContextDetails($c){return in_array($c,['allowed','other'])?['title'=>$c]:false;}},'usercontext'=>new class{public function isContextMember($u,$c){return $c==='allowed';}});
$policy=new publishingpolicy();$policy->init();$GLOBALS['objects']['publishingpolicy']=$policy;
$post=['id'=>'one','userid'=>'author1','post_type'=>'personal','blogid'=>'author1','post_status'=>'draft','datecreated'=>'2009-01-01'];
check(!$policy->canCreate('personal','author1'),'Anonymous cannot publish');
$user->logged=true;check(!$policy->canCreate('personal','author1'),'Readonly cannot publish');check(!$policy->canEdit($post),'Former author cannot edit');
$user->author=true;check($policy->canCreate('personal','author1'),'Author personal');check(!$policy->canCreate('personal','other'),'No another personal blog');check(!$policy->canCreate('site','site'),'Author is not site publisher');
check($policy->canCreate('context','allowed')&&!$policy->canCreate('context','other'),'Explicit course role');
check(!$policy->canRead($post),'Draft not public');$post['post_status']='posted';check($policy->canRead($post),'Published personal readable');$post['post_status']='unknown';check(!$policy->canRead($post),'Unknown status not public');$post['post_status']='draft';
$user->logged=false;check(!$policy->canRead(['post_type'=>'context','blogid'=>'allowed','post_status'=>'posted']),'Anonymous cannot read course content');$user->logged=true;check(!$policy->canRead(['post_type'=>'context','blogid'=>'other','post_status'=>'posted']),'Unrelated course content denied');
$user->author=false;$perms->grants=['personal_publish'];check($policy->canCreate('personal','author1'),'Explicit assistant');$perms->grants=['site_publish'];check($policy->canCreate('site','site'),'Site publisher');
$user->author=true;
$store=new class {public $post,$saved;public function begin(){}public function commit(){}public function rollback(){}public function lockPost($id){return $this->post;}public function post($id){return $this->post;}public function persist($id,$values){$this->saved=$values;return $id?:'new';}public function remove($id){return true;}};$store->post=$post;
$GLOBALS['objects']['publishingstore']=$store;$GLOBALS['objects']['richtextsanitizer']=new class{public function cleanHtml($html){return $html;}};
$service=new publishingservice();$service->init();$input=['title'=>'A title','content'=>'<p>A body</p>','tags'=>'nature','status'=>'draft'];
$input['version']=publishingservice::version($post);
$service->save('one','personal','author1',$input);check(!isset($store->saved['userid'])&&!isset($store->saved['datecreated']),'Preserve original author/date');
foreach ([['one','personal','other',$input],['one','site','site',$input],['one','personal','author1',array_merge($input,['status'=>'other'])]] as $args){try{$service->save(...$args);throw new RuntimeException('Invalid save accepted');}catch(DomainException $e){}}
try{$service->save('one','personal','author1',array_merge($input,['version'=>'stale']));throw new RuntimeException('Stale update accepted');}catch(DomainException $e){check($e->getMessage()==='conflict','Conflict reported');}
require dirname(__DIR__,2).'/contentblocks/classes/compositionservice_class_inc.php';
$GLOBALS['objects']['compositionservice']=new compositionservice();
$featured=$input+['featured_image'=>'/images/bird.jpg','featured_alt'=>'A bird'];
$service->save('one','personal','author1',$featured);
check($store->saved['featured_image']==='/images/bird.jpg'&&$store->saved['featured_alt']==='A bird','Featured image and alternative text persisted');
$featured['featured_image']='javascript:alert(1)';
try{$service->save('one','personal','author1',$featured);throw new RuntimeException('Unsafe featured image accepted');}catch(DomainException $e){}
$featured['featured_image']='';$service->save('one','personal','author1',$featured);check($store->saved['featured_image']==='','Featured image removable');
check(publishingservice::version($post)!==publishingservice::version($post+['featured_image'=>'/images/bird.jpg']),'Featured image included in conflict protection');
$user->id='someoneelse';try{$service->save('one','personal','author1',$input);throw new RuntimeException('Cross-user save accepted');}catch(DomainException $e){}
try{$service->delete('one');throw new RuntimeException('Cross-user delete accepted');}catch(DomainException $e){}
echo "PASS: publishing roles, scope, draft visibility, immutable author/date and mutations\n";
