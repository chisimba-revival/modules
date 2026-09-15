<?php
/** Editorial validation, permissions and transaction boundaries. @author Derek Keats */
if(PHP_SAPI!=='cli')exit(1);
$GLOBALS['kewl_entry_point_run']=true;
class ChisimbaObject{public function getObject($n,$m=''){return $GLOBALS['services'][$n];}public function appendArrayVar($a,$b){}public function getResourceUri($a,$b){return $a;}}
require dirname(__DIR__).'/classes/webinareditservice_class_inc.php';require dirname(__DIR__).'/classes/webinareditpolicy_class_inc.php';
require dirname(__DIR__,2).'/contentblocks/classes/compositionservice_class_inc.php';require dirname(__DIR__,2).'/contentblocks/classes/contentmediaservice_class_inc.php';
require (getenv('CHISIMBA_FRAMEWORK_ROOT')?:dirname(__DIR__,3).'/framework').'/app/core_modules/utilities/classes/richtextsanitizer_class_inc.php';
function check($ok,$why){if(!$ok)throw new RuntimeException($why);}
function rejects($fn,$message){try{$fn();}catch(DomainException $e){check($e->getMessage()===$message,'Wrong rejection '.$e->getMessage());return;}throw new RuntimeException('Accepted '.$message);}
$user=new class{public $logged=true,$admin=true;public function isLoggedIn(){return $this->logged;}public function isAdmin(){return $this->admin;}public function userId(){return 'editor';}};
$permissions=new class{public $granted=false;function areaIdForName($a,$b){return 'webinar';}function rightIdForArea($a,$b){return $b;}function isGranted($u,$r){return $this->granted&&$r==='manage';}};
$store=new class{public $rows=[],$snapshot=[];function begin(){$this->snapshot=$this->rows;}function rollback(){$this->rows=$this->snapshot;}function commit(){}function record($id,$lock=false){return is_string($id)?($this->rows[$id]??null):null;}function persist($r,$old){$this->rows[$r['id']]=$r;}};
$classification=new class{function registerProvider($m,$p){}function assign($m,$id,$k,$ids){if($ids!==[]&&$ids!==['category'])throw new DomainException('classification_unknown_term');}function tag($m,$id,$names){foreach($names as $name)if(str_contains($name,'<'))throw new DomainException('classification_invalid_term');}};
$policy=new webinareditpolicy();$GLOBALS['services']=['user'=>$user,'permissionservice'=>$permissions,'webinareditpolicy'=>$policy,'webinareditstore'=>$store,'classificationservice'=>$classification,'compositionservice'=>new compositionservice(),'contentmediaservice'=>new contentmediaservice(),'richtextsanitizer'=>new richtextsanitizer()];
$service=new webinareditservice();$service->init();
check($policy->canManage(),'Admin');$user->admin=false;check(!$policy->canManage(),'Ordinary member denied');$permissions->granted=true;check($policy->canManage(),'Explicit manager');$user->logged=false;check(!$policy->canManage(),'Anonymous denied despite stale grant');rejects(fn()=>$service->save('','speaker',[]),'editor_forbidden');$user->logged=true;$user->admin=true;
$speaker=$service->save('','speaker',['title'=>'Test speaker','description'=>'<p>A biography</p>','image'=>'','image_alt'=>'','status'=>'published']);
$input=['create_id'=>str_repeat('a',32),'title'=>'Test webinar','description'=>'<p>Birds <strong>and habitats</strong><script>bad()</script></p>','image'=>'','image_alt'=>'','status'=>'published','starts_at'=>'2026-10-15T19:00','ends_at'=>'2026-10-15T20:00','timezone'=>'Africa/Johannesburg','recording'=>'https://youtu.be/7lRvYUfMwDs','joining_url'=>'https://example.org/join','speakers'=>[$speaker['id']],'tags'=>'birds, ecology','categories'=>['category'],'registration_open'=>'1','cancelled'=>''];
$row=$service->save('','webinar',$input);$payload=json_decode($row['payload'],true);check(!str_contains($payload['description'],'<script'),'Sanitised description');check($row['presented_at']==='2026-10-15 19:00:00','Local time preserved');check($payload['joining_url']==='https://example.org/join','Private joining link retained');
rejects(fn()=>$service->save('','webinar',$input),'editor_conflict');
$input['version']=webinareditservice::version($row);$input['title']='Edited webinar';$edited=$service->save($row['id'],'webinar',$input);check($edited['source_key']===$row['source_key'],'Stable record identity');check(json_decode($edited['payload'],true)['original_source_hash']===$row['source_hash'],'Original hash retained');rejects(fn()=>$service->save($row['id'],'webinar',$input),'editor_conflict');
$input['version']=webinareditservice::version($edited);$input['categories']=['unknown'];rejects(fn()=>$service->save($row['id'],'webinar',$input),'classification_unknown_term');check($store->rows[$row['id']]===$edited,'Failed taxonomy rolls content back');$input['categories']=[];
foreach(['javascript:alert(1)','https://user:password@example.org/join'] as $url){$bad=$input;$bad['joining_url']=$url;rejects(fn()=>$service->values('webinar',$bad,$edited),'editor_url');}
rejects(fn()=>webinareditservice::localDate('2026-02-31T19:00','Africa/Johannesburg'),'editor_date');rejects(fn()=>webinareditservice::localDate('2026-03-29T01:30','Europe/London'),'editor_date');
$bad=$input;$bad['ends_at']=$bad['starts_at'];rejects(fn()=>$service->values('webinar',$bad,$edited),'editor_end');$bad=$input;$bad['speakers']=[];rejects(fn()=>$service->values('webinar',$bad,$edited),'editor_speaker');
$draft=$input;$draft['status']='draft';$draft['description']='';$draft['starts_at']=$draft['ends_at']='';$draft['speakers']=[];check($service->values('webinar',$draft,$edited)['presented_at']==='','Incomplete draft allowed');
check(webinareditservice::version(['presented_at'=>null])===webinareditservice::version(['presented_at'=>'']),'Database null/empty stable version');
echo "PASS: manager/denied permissions, sanitisation, dates/DST, private URLs, required speakers, draft, immutable identity, duplicate/concurrent saves and rollback\n";
