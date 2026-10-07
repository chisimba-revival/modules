<?php
$GLOBALS['kewl_entry_point_run']=true;
class ChisimbaObject {public function getObject($name,$module=''){return $GLOBALS['services'][$name];}public function appendArrayVar($name,$value){}public function getResourceUri($file,$module){return $file;}}
require dirname(__DIR__).'/classes/shoprules.php';require dirname(__DIR__).'/classes/shopsalerules.php';require dirname(__DIR__).'/classes/salepromotion_class_inc.php';
require dirname(__DIR__,2).'/contentblocks/classes/compositionservice_class_inc.php';
require dirname(__DIR__,3).'/framework/app/core_modules/utilities/classes/richtextsanitizer_class_inc.php';
$shop=new class {
 public $on=true,$stock=1;
 public function sale(){return ['enabled'=>$this->on,'percent'=>40,'starts_at'=>time()-5,'ends_at'=>time()+3600,'title'=>'Opening sale','description'=>'<script>not executable</script>','image_url'=>'https://example.invalid/cover.webp','button_label'=>'Shop sale'];}
 public function books(){return [['sale_price_minor'=>11100,'stock'=>$this->stock]];}
 public function url($action){return '/index.php?module=shop&action='.$action;}
 public function text($key){return $key;}
};
$GLOBALS['services']=['shopservice'=>$shop,'richtextsanitizer'=>new richtextsanitizer,'compositionservice'=>new compositionservice];
$p=new salepromotion;$html=$p->renderBlock([]);
if(!str_contains($html,'<img')||!str_contains($html,'Opening sale')||!str_contains($html,'module=shop&amp;action=catalogue')||str_contains($html,'<script>'))throw new RuntimeException('Promotion rendering failed');
$shop->on=false;if($p->renderBlock([])!=='')throw new RuntimeException('Disabled sale advertised');
if($p->previewBlock([])==='')throw new RuntimeException('Missing editor explanation');
$shop->on=true;$shop->stock=0;if($p->renderBlock([])!=='')throw new RuntimeException('Sold-out sale advertised');
echo "PASS: promotion image, text, CTA, escaping, inactive preview and public visibility\n";

$shop->on=true;$shop->stock=1;
$id=str_repeat('a',32);$html=$p->renderBlock(['scroll_product_id'=>$id]);
if(!str_contains($html,'href="#shop-product-'.$id.'"')||str_contains($html,'action=catalogue'))throw new RuntimeException('Same-page CTA failed');
try{$p->validateBlock(['scroll_product_id'=>'bad" onclick="alert(1)']);throw new RuntimeException('Invalid target accepted');}catch(DomainException $expected){}
echo "PASS: same-page promotion target and validation\n";
