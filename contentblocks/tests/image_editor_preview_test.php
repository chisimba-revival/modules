<?php
/** Active image fields retain previews without trusting unsafe media addresses. */
$GLOBALS['kewl_entry_point_run']=true;
class ChisimbaObject {
 public function getObject($name,$module='') {return $name==='compositionservice'?new compositionservice():new class {public function render(...$args){return '';}public function code2Txt($key,...$args){return $key;}};}
}
require dirname(__DIR__).'/classes/compositionservice_class_inc.php';
require dirname(__DIR__).'/classes/compositioneditor_class_inc.php';
$editor=new compositioneditor();$field=new ReflectionMethod($editor,'field');
foreach(['/wp-content/uploads/2026/03/friendship-wall-1.png','/usrfiles/public/wordpress/2026/03/friendship-wall-1.png','https://example.org/photo.jpg'] as $url){
 $html=$field->invoke($editor,'content_blocks[0]','url',$url,'image_url',true);
 if(!str_contains($html,'data-composition-image-preview=')||!str_contains($html,'src="'.$url.'"'))throw new RuntimeException('Current image lost');
}
foreach(['javascript:alert(1)','//example.org/photo.jpg',''] as $url){
 $html=$field->invoke($editor,'content_blocks[0]','url',$url,'image_url',true);
 if(str_contains($html,' src='))throw new RuntimeException('Unsafe preview address');
}
$html=$field->invoke($editor,'content_blocks[0]','url','https://example.org/video','video_url',true);
if(str_contains($html,'<img'))throw new RuntimeException('Video treated as image');
echo "PASS: active imported and native image previews, unsafe addresses hidden, video unchanged.\n";
