<?php
$GLOBALS['kewl_entry_point_run']=true;
class ChisimbaObject {
 public function getObject($name,$module=''){return $GLOBALS['services'][$name];}
 public function newObject($name,$module=''){$o=new $name();$o->init();return $o;}
 public function appendArrayVar($name,$value){}
 public function getResourceUri($file,$module){return '/resources/'.$file;}
}
$framework=getenv('CHISIMBA_FRAMEWORK_ROOT')?:dirname(__DIR__,3).'/framework';
foreach(['utilities/classes/richtextsanitizer','ui/classes/iconservice','ui/classes/window'] as $file)require $framework.'/app/core_modules/'.$file.'_class_inc.php';
foreach(['compositionservice','contentmediaservice','videocollection'] as $class)require dirname(__DIR__).'/classes/'.$class.'_class_inc.php';
require dirname(__DIR__,2).'/simpleblog/classes/wordpresspostconverter_class_inc.php';
$GLOBALS['services']=['richtextsanitizer'=>new richtextsanitizer(),'iconservice'=>new iconservice(),'contentmediaservice'=>new contentmediaservice(),'videocollection'=>new videocollection(),'compositionservice'=>new compositionservice(),'language'=>new class {function code2Txt($key,$module){return $key;}},'uiservice'=>new class {function show(){}}];
function check($ok,$message){if(!$ok)throw new RuntimeException($message);}
function rejects($fn){try{$fn();}catch(DomainException $e){return;}throw new RuntimeException('Invalid video accepted');}
$s=$GLOBALS['services']['compositionservice'];$g=$s->emptyBlock('video_gallery');
$first=['id'=>'2','title'=>'Earlier','url'=>'https://www.tiktok.com/@example/video/1234567890123456789','date'=>'2026-04-01 09:00:00','thumbnail'=>'/managed/first.jpg','topic'=>'Grasses'];
$last=array_replace($first,['id'=>'10','title'=>'Later','date'=>'2026-04-02 09:00:00']);
$g['videos']=[$last,$first];$g['gallery_order']='asc';$html=$s->render([$g]);
check(strpos($html,'Earlier')<strpos($html,'Later'),'Oldest first');
check(!str_contains($html,'<iframe')&&substr_count($html,'data-collection-card')===2,'Lazy two-card player');
check(str_contains($html,'<dialog')&&str_contains($html,'data-ui-close'),'Shared native dialog');
check($html===$s->render([$g]),'Repeatable import rendering');
$g['gallery_order']='desc';$html=$s->render([$g]);check(strpos($html,'Later')<strpos($html,'Earlier'),'Newest first');
$valid=$s->validate([$g]);check($valid===$s->validate($valid),'Editor data round trip');
$copy=$s->command($valid,'duplicate:'.$g['id']);check($copy[0]['id']!==$copy[1]['id']&&$copy[0]['videos']===$copy[1]['videos'],'Independent gallery duplicate');
$added=$s->command($valid,'gallery_add:'.$g['id']);check(count($added[0]['videos'])===3,'Add draft item');
check(substr_count($s->render($added),'data-collection-card')===2,'Incomplete draft item hidden');
$removed=$s->command($added,'gallery_remove:'.$g['id'].':0');check(count($removed[0]['videos'])===2,'Remove one item');
foreach(['javascript:alert(1)','https://tiktok.com.evil.test/@a/video/1234567890123456789','https://user:pass@www.tiktok.com/@a/video/1234567890123456789'] as $url){$bad=$g;$bad['videos'][0]['url']=$url;rejects(fn()=>$s->validate([$bad]));}
$bad=$g;$bad['videos'][0]['thumbnail']='javascript:alert(1)';rejects(fn()=>$s->validate([$bad]));
$bad=$g;$bad['videos'][0]['date']='2026-02-31 09:00:00';rejects(fn()=>$s->validate([$bad]));
$bad=$g;$bad['videos'][0]['id']=$bad['videos'][1]['id'];rejects(fn()=>$s->validate([$bad]));
$c=new wordpresspostconverter();$c->init();$short='[TIKTOK_VERTICAL_GALLERY topic="grasses"]';
$p=['ID'=>215,'post_title'=>'Grasses','post_content'=>'','widgets'=>[['type'=>'shortcode','settings'=>['shortcode'=>$short]]],'reviewed_shortcodes'=>[$short=>['type'=>'video_gallery','order'=>'asc','videos'=>[$first,$last]]]];
$converted=$c->convert($p,['/managed/first.jpg'=>'/rewritten.jpg']);check(count($converted)===1&&$converted[0]['type']==='video_gallery'&&count($converted[0]['videos'])===2&&$converted[0]['videos'][0]['thumbnail']==='/rewritten.jpg','Reviewed importer retains collection metadata and rewrites media');
echo "PASS: gallery ordering, safe providers, dates, lazy dialog, editor round trip, duplicate/add/remove and importer metadata\n";
