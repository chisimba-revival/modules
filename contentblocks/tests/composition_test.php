<?php
/** Behavioural tests for shared composition, independent of its owning module. @author Derek Keats */
if(PHP_SAPI!=='cli')exit(1);
$GLOBALS['kewl_entry_point_run']=true;
class ChisimbaObject { public function getObject($name,$module=''){return $GLOBALS['services'][$name];} public function appendArrayVar($name,$value){} public function getResourceUri($file,$module){return $file;} }
require dirname(__DIR__).'/classes/compositionservice_class_inc.php';
require dirname(__DIR__).'/classes/contentmediaservice_class_inc.php';
require dirname(__DIR__,3).'/framework/app/core_modules/utilities/classes/richtextsanitizer_class_inc.php';
$GLOBALS['services']=['richtextsanitizer'=>new richtextsanitizer(),'contentmediaservice'=>new contentmediaservice(),'language'=>new class {function code2Txt($code,$module){return $code;}}];
$s=new compositionservice();
function check($ok,$message){if(!$ok)throw new RuntimeException($message);}
function rejects($fn){try{$fn();}catch(DomainException $e){return;}throw new RuntimeException('Invalid composition accepted');}
$a=$s->emptyBlock('text');$a['text']='<p>First <strong>section</strong></p><script>bad()</script>';
$b=$s->emptyBlock('hero');$b['url']='/images/test.jpg';$b['alt']='Image description';$b['text']='<p>Second</p>';
$cta=$b;$cta['button_label']='Read more';$cta['button_url']='https://example.org/article';
check(str_contains($s->render([$cta]),'href="https://example.org/article"'),'Hero CTA rendered');
foreach(['hero','reverse_hero'] as $type)foreach(['top-left','top-right','bottom-left','bottom-right'] as $position){
 $cta['type']=$type;$cta['button_position']=$position;
 check(str_contains($s->render([$cta]),'chisimba-hero-image__action--'.$position),'Hero overlay placement');
}
$cta['button_position']='bad';rejects(fn()=>$s->validate([$cta]));$cta['button_position']='text';
$cta['button_url']='javascript:alert(1)';rejects(fn()=>$s->validate([$cta]));
$cta['button_url']='';check(!str_contains($s->render([$cta]),'<a '),'Incomplete CTA hidden');
$blocks=$s->validate([$a,$b]);check(!str_contains($blocks[0]['text'],'script'),'Sanitise text');
$blocks=$s->command($blocks,'up:'.$b['id']);check($blocks[0]['id']===$b['id'],'Reorder identity');
$copy=$s->command($blocks,'duplicate:'.$a['id']);check(count($copy)===3&&$copy[1]['id']!==$copy[2]['id']&&$copy[1]['text']===$copy[2]['text'],'Independent duplicate');
$copy=$s->command($copy,'before:'.$copy[2]['id'].':'.$copy[0]['id']);check($copy[0]['text']===$a['text']||str_contains($copy[0]['text'],'First'),'Drag reorder');
$inserted=$s->command($blocks,'addbefore:text:'.$blocks[0]['id']);check(count($inserted)===3&&$inserted[1]['id']===$blocks[0]['id'],'Palette drop at insertion point');
$ended=$s->command($blocks,'end:'.$blocks[0]['id']);check($ended[1]['id']===$blocks[0]['id'],'Drag to end');
check($s->command($blocks,'focus:'.$blocks[0]['id'])===$blocks,'Focus preserves unsaved content');
$rendered=$s->render($blocks);check(strpos($rendered,'Second')<strpos($rendered,'First'),'Render order');check(str_contains($rendered,'alt="Image description"'),'Alt retained');
foreach(['javascript:alert(1)','//evil.example/a','data:image/svg+xml,<svg>','https://user:pass@example.com/a'] as $url){$bad=$b;$bad['url']=$url;rejects(fn()=>$s->validate([$bad]));}
rejects(fn()=>$s->validate([$a,$a]));$unknown=$a;$unknown['type']='missing';rejects(fn()=>$s->validate([$unknown]));
$legacy=$s->fromPost(['post_content'=>'<p>Old content</p>']);check(count($legacy)===1&&$legacy[0]['text']==='<p>Old content</p>','Legacy conversion retains original');
$video=$s->emptyBlock('video');$video['url']='https://youtu.be/dQw4w9WgXcQ';check(str_contains($s->render([$video]),'youtube-nocookie.com/embed/dQw4w9WgXcQ'),'Shared video service');
check($s->render([$s->emptyBlock('text')])==='','Empty placeholder not published');
$slider=$s->emptyBlock('slider');$slider['slides']=[['url'=>'/a.jpg','alt'=>'First image','caption'=>'Caption'],['url'=>'/b.jpg','alt'=>'Second image','caption'=>'']];
$sliderHtml=$s->render([$slider]);check(substr_count($sliderHtml,'<img')===2&&str_contains($sliderHtml,'tabindex="0"')&&!str_contains($sliderHtml,'autoplay'),'Accessible non-autoplay gallery');
$s->registerType('custom','file-text',new class {function label(){return 'Custom';}function validateBlock($data){return ['value'=>(string)($data['value']??'')];}function renderBlock($data){return '<p>'.htmlspecialchars($data['value'],ENT_QUOTES).'</p>';}function editBlock($prefix,$data){return '';}});
$custom=$s->emptyBlock('custom');$custom['data']=['value'=>'Reusable'];check($s->render([$custom])==='<p>Reusable</p>','Registered extension');
echo "PASS: composition validation, safe media, stable ordering, duplication, legacy content, sliders and extension providers\n";
