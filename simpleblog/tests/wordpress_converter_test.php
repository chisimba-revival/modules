<?php
/** Preserve authored content while removing WordPress/plugin execution. */
$GLOBALS['kewl_entry_point_run']=true;
class ChisimbaObject { public function getObject($n,$m=''){return $GLOBALS['services'][$n];} public function appendArrayVar($n,$v){} public function getResourceUri($f,$m){return $f;} }
$modules=dirname(__DIR__,2);$framework=getenv('CHISIMBA_FRAMEWORK_ROOT')?:dirname($modules).'/framework';
require $modules.'/contentblocks/classes/compositionservice_class_inc.php';
require $modules.'/contentblocks/classes/contentmediaservice_class_inc.php';
require $framework.'/app/core_modules/utilities/classes/richtextsanitizer_class_inc.php';
require dirname(__DIR__).'/classes/wordpresspostconverter_class_inc.php';
$b=new compositionservice();$GLOBALS['services']=['compositionservice'=>$b,'contentmediaservice'=>new contentmediaservice(),'richtextsanitizer'=>new richtextsanitizer(),'language'=>new class{function code2Txt($c,$m){return $c;}}];
$c=new wordpresspostconverter();$c->init();
$p=['ID'=>'42','post_title'=>'Birds','post_content'=>'legacy','widgets'=>[
 ['type'=>'heading','settings'=>['title'=>'Birds']],
 ['type'=>'text-editor','settings'=>['editor'=>'<p>São Tomé’s <strong>birds</strong><script>bad()</script></p>']],
 ['type'=>'image','settings'=>['image'=>['url'=>'https://old.test/photo.jpg','alt'=>'Bird','caption'=>'Photo credit']]],
 ['type'=>'image-carousel','settings'=>['carousel'=>[['url'=>'https://old.test/photo.jpg','caption'=>'Slide credit']]]],
 ['type'=>'html','settings'=>['html'=>'<iframe src="https://xeno-canto.org/395736/embed"></iframe>']],
 ['type'=>'video','settings'=>['youtube_url'=>'https://youtu.be/7lRvYUfMwDs']],
 ['type'=>'post-comments','settings'=>[]]
]];
$out=$c->convert($p,['https://old.test/photo.jpg'=>'/managed/photo.jpg']);
if(count($out)!==5||$out[1]['caption']!=='Photo credit'||$out[2]['slides'][0]['caption']!=='Slide credit')throw new RuntimeException('Authored blocks/captions lost');
$html=$b->render($out);foreach(['São Tomé’s','<strong>birds</strong>','/managed/photo.jpg','xeno-canto.org/395736/embed','youtube-nocookie.com/embed/7lRvYUfMwDs'] as $text)if(!str_contains($html,$text))throw new RuntimeException('Content lost: '.$text);
if(str_contains($html,'<script')||str_contains($html,'old.test'))throw new RuntimeException('Unsafe or unmigrated content');
if($out!==$c->convert($p,['https://old.test/photo.jpg'=>'/managed/photo.jpg']))throw new RuntimeException('Unstable block IDs');
$p['widgets'][]=['type'=>'unknown-plugin','settings'=>[]];try{$c->convert($p,[]);throw new LogicException('Unknown plugin accepted');}catch(RuntimeException $expected){}
echo "PASS: Unicode, prose, image/gallery credits, media rewrites, bird audio, YouTube, deterministic blocks and unknown-widget rejection\n";
