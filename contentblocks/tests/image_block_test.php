<?php
/** Image-only persistence, safe caption output and shared editor behaviour. */
require __DIR__.'/composition_test.php';
$image=$s->command([],'add:image')[0];
check($image['type']==='image','Palette command creates image');
$image['url']='/images/bird.jpg';$image['alt']='Bird & nest';$image['caption']='Caption <script>alert(1)</script> & credit';
$saved=$s->validate([$image]);
$restored=$s->fromPost(['composition_json'=>json_encode(['version'=>1,'blocks'=>$saved])]);
$html=$s->render($restored);
check(str_contains($html,'<figure><img')&&str_contains($html,'<figcaption>Caption &lt;script&gt;alert(1)&lt;/script&gt; &amp; credit</figcaption>'),'Semantic escaped caption survives save/reload');
check(str_contains($html,'alt="Bird &amp; nest"'),'Alt text stays separate');
check(!str_contains($html,'<div>')&&!str_contains($html,'<h2>'),'No empty text column or heading');
$image['caption']='';check(!str_contains($s->render([$image]),'<figcaption>'),'Caption optional');
$image['url']='';$image['caption']='No file yet';check($s->render([$image])==='','Incomplete draft stays unpublished');
$image['url']='javascript:alert(1)';rejects(fn()=>$s->validate([$image]));
$image['url']='/images/bird.jpg';$image['image_size']='portrait';check(str_contains($s->render([$image]),'chisimba-content-portrait'),'Existing portrait sizing supported');
$copy=$s->command([$image],'duplicate:'.$image['id']);check($copy[0]['caption']===$copy[1]['caption']&&$copy[0]['id']!==$copy[1]['id'],'Duplication preserves caption with fresh identity');
require dirname(__DIR__).'/classes/compositioneditor_class_inc.php';
class ImageBlockEditorFixture extends compositioneditor {
 public function uri(...$args) {return "index.php";}
 public function newObject($name,$module='') {return new class {function show(){return '';}};}
 public function getObject($name,$module='') {
  global $s;
  return $name==='compositionservice'?$s:new class {function render(...$args){return '';}};
 }
}
$editor=new ImageBlockEditorFixture();$form=$editor->show([$image]);
foreach(['url','alt','caption','image_size'] as $field)check(str_contains($form,'name="content_blocks[0]['.$field.']"'),'Image field '.$field);
foreach(['title','text','button_label','button_url'] as $field)check(!str_contains($form,'name="content_blocks[0]['.$field.']"'),'No irrelevant field '.$field);
check(str_contains($form,'data-composition-image-preview')&&str_contains($form,'addafter:image:'),'Managed image preview and insertion controls');
foreach(['simpleblog','sitepages'] as $module)check(str_contains(file_get_contents(dirname(__DIR__,2).'/'.$module.'/classes/helpcontent_class_inc.php'),"text('image_help')"),'Shared image Help in '.$module);
echo "PASS image-only round trip, captions, alt text, safe URLs, drafts, sizes, duplication and editor controls.\n";
