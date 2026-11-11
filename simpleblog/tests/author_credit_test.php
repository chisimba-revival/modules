<?php
$GLOBALS['kewl_entry_point_run']=true;
class ChisimbaObject {
 public function getObject($name,$module=null){return $GLOBALS['objects'][$name];}
 public function uri($params,$module){return 'https://example.test/index.php?'.http_build_query(['module'=>$module]+$params);}
}
require dirname(__DIR__).'/classes/publishingauthorservice_class_inc.php';
require dirname(__DIR__).'/classes/publishingservice_class_inc.php';
function check($value,$message){if(!$value)throw new RuntimeException($message);}
$modules=new class {public $installed=true;function checkIfRegistered($m){return $this->installed;}};
$users=new class {public $active=true;function findByUserId($id){return $id==='u1'?['isactive'=>$this->active?'1':'0','firstname'=>'Pat','surname'=>'Bird']:null;}function authorNameSuggestions($q){return [['userid'=>'u1']];}};
$bios=new class {public $visible=true;function publicPerson($id){return $this->visible?['biography'=>'Public bio']:null;}};
$speakers=new class {public $visible=true;function one($id){return $this->visible&&$id==='s1'?['id'=>$id,'kind'=>'speaker','title'=>'Pat Bird']:null;}function speakerNameSuggestions($q){return [['id'=>'s1']];}};
$GLOBALS['objects']=['modules'=>$modules,'userservice'=>$users,'authorbiographyservice'=>$bios,'webinarstore'=>$speakers];
$s=new publishingauthorservice();
$manual=$s->values(['author_credit'=>' Guest Writer '],null);check($manual===['author_credit'=>'Guest Writer','author_kind'=>null,'author_id'=>null],'Plain credit without account');
$input=['author_credit'=>'Pat Bird','author_kind'=>'speaker','author_id'=>'s1'];$saved=$s->values($input,null);check(str_contains($s->link($saved),'module=webinar'),'Speaker biography URL');
$matches=$s->suggestions('Pat');check(count($matches)===2&&$matches[0]['kind']!==$matches[1]['kind'],'Duplicate names remain explicit distinct choices');
check($s->suggestions('P')===[]&&$s->suggestions(str_repeat('x',121))===[],'Bounded query');
$modules->installed=false;unset($GLOBALS['objects']['webinarstore']);check(count($s->suggestions('Pat'))===1,'No optional module access when absent');check($s->link($saved)==='','Unavailable module degrades to text');check($s->values($input,$saved)===$saved,'Existing credit retained if module removed');
$u=['author_credit'=>'Pat Bird','author_kind'=>'user','author_id'=>'u1'];check(str_contains($s->link($u),'action=biography')&&str_contains($s->link($u),'userid=u1'),'Public user bio URL has logical identity');
$bios->visible=false;check($s->link($u)==='','No empty biography link');check($s->values($u,null)===$u,'Can credit active user without a biography');
$users->active=false;check($s->link($u)==='','Inactive user biography unavailable');
foreach([['author_credit'=>['bad']],['author_credit'=>str_repeat('x',251)],['author_credit'=>"Pat\nBird"],['author_credit'=>'Spoof','author_kind'=>'user','author_id'=>'u1'],['author_credit'=>'Bad','author_kind'=>'url','author_id'=>'javascript:bad']] as $bad){try{$s->values($bad,null);throw new LogicException('Invalid credit accepted');}catch(DomainException $e){check($e->getMessage()==='invalid_author','Actionable validation');}}
check($s->values(['author_credit'=>'','author_kind'=>'speaker','author_id'=>'s1'],$saved)===['author_credit'=>null,'author_kind'=>null,'author_id'=>null],'Clearing credit restores account default');
check(publishingservice::version(['id'=>'post','author_credit'=>'A'])!==publishingservice::version(['id'=>'post','author_credit'=>'B']),'Credit participates in conflict detection');
check(publishingservice::version(['id'=>'post','author_id'=>'u1'])!==publishingservice::version(['id'=>'post','author_id'=>'u2']),'Biography reference participates in conflict detection');
echo "PASS author credits, explicit identities, optional webinars, unavailable biographies, validation and stale saves\n";
