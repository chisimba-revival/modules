<?php
$GLOBALS['kewl_entry_point_run']=true;
class ChisimbaObject {public function getObject($name,$module=''){return $GLOBALS['objects'][$name];}}
require dirname(__DIR__).'/classes/contentpaths_class_inc.php';
function check($v,$message){if(!$v)throw new RuntimeException($message);}
$store=new class {
 public $rows=[],$legacy=['printed-qr'];
 function owner($m,$id){foreach($this->rows as $r)if($r['module_id']===$m&&$r['content_id']===$id&&$r['canonical'])return $r;return null;}
 function path($slug){return $this->rows[$slug]??null;}
 function legacyPathExists($slug){return in_array($slug,$this->legacy,true);}
 function claim($m,$id,$slug){foreach($this->rows as &$r)if($r['module_id']===$m&&$r['content_id']===$id)$r['canonical']=0;unset($r);$this->rows[$slug]=['module_id'=>$m,'content_id'=>$id,'slug'=>$slug,'canonical'=>1];}
 function locked($fn){return $fn();}
};
$GLOBALS['objects']=['contentpathstore'=>$store,'dbsysconfig'=>new class{function getValue(...$a){return 'TRUE';}},'altconfig'=>new class{function getSiteRoot(){return 'https://example.test/ch/';}},'modules'=>new class{function checkIfRegistered($m){return true;}},'dbsitepages'=>new class{function findBySlug($s){return $s==='existing-page'?['id'=>'existing']:null;}}];
$p=new contentpaths();
check(contentpaths::slug('Chisimba runs Kenga Learn!')==='chisimba-runs-kenga-learn','Title slug');
check(contentpaths::slug('Café birds')==='cafe-birds','Accents');
foreach(['../x','a/b','login','go','wp-admin','a?x=1','a%2fb'] as $s)check(!contentpaths::valid($s),'Unsafe/reserved '.$s);
check($p->save('simpleblog','one','Birds')==='birds','First');
check($p->save('sitepages','two','Birds')==='birds-2','Cross-owner suffix');
check($p->save('webinar','webinar-one','Birds')==='birds-3','Webinars share the namespace');
check($p->resolveRequest('/ch/birds-3')['module']==='webinar','Webinar owner routing');
check($p->save('simpleblog','one','New title')==='birds','Title change stable');
check($p->save('simpleblog','one','New title','Bird-Stories')==='bird-stories','Manual normalisation');
check($p->resolveRequest('/ch/birds?module=security&action=delete')['id']==='one','Old alias and query cannot retarget');
check($p->resolveRequest('/ch/BIRD-STORIES/')['id']==='one','Case/trailing slash');
check($p->resolveRequest('/other/birds')===null && $p->resolveRequest('/ch/no-such-page')===null,'Unknown scope/path');
check($p->save('simpleblog','three','Existing page')==='existing-page-2','Unconverted page reserved');
foreach(['printed-qr','birds','existing-page'] as $s){try{$p->save('simpleblog','new','Title',$s);throw new RuntimeException('Accepted collision');}catch(DomainException $e){check($e->getMessage()==='slug_taken','Collision message');}}
check($p->url('simpleblog','one','fallback')==='https://example.test/ch/bird-stories','Canonical URL');
check($p->url('simpleblog','missing','fallback')==='fallback','Unmigrated fallback');
$GLOBALS['objects']['dbsysconfig']=new class{function getValue(...$a){return 'FALSE';}};
check(!$p->enabled() && $p->url('simpleblog','one','old-id-url')==='old-id-url' && $p->resolveRequest('/ch/bird-stories')===null,'Disabled flag preserves ID fallback');
echo "PASS generated/manual paths, collisions, retained aliases, reserved names, queries and subdirectory roots\n";
