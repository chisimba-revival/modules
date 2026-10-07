<?php
$GLOBALS['kewl_entry_point_run']=true;
class ChisimbaObject {public $service;public function getObject($name,$module){return $this->service;}}
require dirname(__DIR__).'/classes/productbuttons_class_inc.php';
$p=new productbuttons;
$p->service=new class {
 public $book=['sale_price_minor'=>11100];
 public function book($id){return $this->book;}
 public function text($key){return 'Sale';}
};
if($p->mediaBadge(['product_id'=>'fixture'])!=='Sale')throw new RuntimeException('Active sale badge absent');
$p->service->book=['sale_price_minor'=>null];
if($p->mediaBadge(['product_id'=>'fixture'])!=='')throw new RuntimeException('Regular price badge present');
$p->service->book=null;
if($p->mediaBadge(['product_id'=>'fixture'])!=='')throw new RuntimeException('Unavailable product badge present');
echo "PASS: active sale, regular price and unavailable image badges\n";
