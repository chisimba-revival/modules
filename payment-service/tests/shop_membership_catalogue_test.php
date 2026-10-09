<?php
/** Shop offer publication never changes historic subscription price lookup. */
$GLOBALS['kewl_entry_point_run']=true;
class ChisimbaObject{function getObject($name,$module=''){return $GLOBALS['objects'][$name];}}
require dirname(__DIR__).'/classes/paymentcatalogservice_class_inc.php';
$old='shop-'.str_repeat('a',32).'-1';$current='shop-'.str_repeat('a',32).'-2';
$products=new class($old,$current){public $rows;function __construct($old,$new){$this->rows=[['id'=>'old','code'=>$old,'purpose_type'=>'membership','purpose_id'=>'tier_1','billing_period'=>'monthly','active'=>1],['id'=>'new','code'=>$new,'purpose_type'=>'membership','purpose_id'=>'tier_1','billing_period'=>'annual','active'=>1]];}function activeProducts(){return $this->rows;}function allProducts(){return $this->rows;}function byCode($code){foreach($this->rows as $r)if($r['code']===$code)return $r;return null;}};
$prices=new class{function currentForProduct($id){return ['amount_minor'=>$id==='old'?1000:10000,'currency'=>'ZAR','version_code'=>'1','effective_from'=>'2020-01-01 00:00:00','effective_until'=>null];}function forProduct($id){return [$this->currentForProduct($id)];}function byVersion($id,$version){return $version==='1'?$this->currentForProduct($id):null;}};
$shop=new class($current){public $enabled=true;function __construct(public $code){}function membershipProductAvailable($code){return $this->enabled&&$code===$this->code;}};
$GLOBALS['objects']=['dbpaymentproducts'=>$products,'dbpaymentprices'=>$prices,'dbcontext'=>new stdClass,'membershipservice'=>new class{function tiers($active){return ['tier_1'=>[]];}},'shopservice'=>$shop];
$catalogue=new paymentcatalogservice;$catalogue->init();
function check($v,$m){if(!$v)throw new RuntimeException($m);}
check($catalogue->purchasable($old)===null,'Retired offer cannot start checkout');
check($catalogue->purchasable($current)['price']['amount_minor']===10000,'Current offer can start checkout');
check(count($catalogue->listProducts(true))===1,'Public catalogue excludes retired offer');
$shop->enabled=false;check($catalogue->listProducts(true)===[]&&$catalogue->purchasable($current)===null,'Archived or disabled shop offer unavailable');
check($catalogue->productVersion($old,'1')['price']['amount_minor']===1000,'Renewals retain the original price after archival or repricing');
echo "PASS: Shop membership publication, immutable price versions and continued historic renewals.\n";
