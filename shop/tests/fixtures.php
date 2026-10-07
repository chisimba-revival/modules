<?php
/** Synthetic boundary fixtures: never make external calls. */
$GLOBALS['kewl_entry_point_run']=true;
if (!class_exists('ChisimbaObject')) { class ChisimbaObject {} }
require_once dirname(__DIR__).'/classes/shopservice_class_inc.php';
class ShopMemoryStore {
 public $tables=['settings'=>['shop'=>['id'=>'shop','revision'=>1,'settings_json'=>'{"enabled":false,"terms":"","zones":{}}']],'books'=>[],'orders'=>[],'history'=>[]];
 public function one($t,$id){return $this->tables[$t][$id]??null;}
 public function rows($t,$where=[],$limit=null){$rows=array_values(array_filter($this->tables[$t],function($r)use($where){foreach($where as $k=>$v)if((string)$r[$k]!== (string)$v)return false;return true;}));return $limit?array_slice($rows,0,$limit):$rows;}
 public function add($t,$row){if(isset($this->tables[$t][$row['id']]))throw new RuntimeException('duplicate');$this->tables[$t][$row['id']]=$row;return $row;}
 public function save($t,$id,$changes){$this->tables[$t][$id]=array_merge($this->tables[$t][$id],$changes);}
 public function transaction($fn){$before=$this->tables;try{return $fn();}catch(Throwable $e){$this->tables=$before;throw $e;}}
 public function reserved($id,$now,$except=''){$n=0;foreach($this->tables['orders'] as $o){if($o['id']===$except||$o['payment_state']!=='unpaid'||$o['fulfilment_state']!=='held'||$o['hold_until']<=$now)continue;foreach(ShopRules::inventory(json_decode($o['snapshot'],true)) as $line)if($line['book_id']===$id)$n+=$line['quantity'];}return $n;}
 public function recentOrders(){return $this->rows('orders');}
}
class ShopFixtureUser {public $admin=true;public function isLoggedIn(){return $this->admin;}public function isAdmin(){return $this->admin;}public function userId(){return $this->admin?'fixture-manager':'';}}
class ShopFixtureConfig {public function getSiteName(){return 'Fixture Books';}public function getValue($k,$m){return str_repeat('d',64);}public function getSiteRoot(){return 'https://shop.test/';}}
class ShopFixtureLanguage {public function languageText($key,$module){return $key;}}
class ShopFixtureMail {public $rows=[];public $fail=false;public function queueEmail($m){if($this->fail)return ['ok'=>false];$this->rows[$m['idempotencyKey']]=$m;return ['ok'=>true];}}
class ShopFixturePayments {
 public $rows=[];public $calls=0;public $uncertain=false;public $available=true;
 public function intent($id){return $this->rows[$id]??null;}
 public function providerAvailable($p){return $p==='paystack'&&$this->available;}
 public function createIntent($i){foreach($this->rows as $r)if($r['idempotency_key']===$i['idempotencyKey'])return ['ok'=>true,'intentId'=>$r['id']];$id=bin2hex(random_bytes(16));$r=['id'=>$id,'state'=>'created'];foreach(['user_id'=>'userId','purpose_type'=>'purposeType','purpose_id'=>'purposeId','product_code'=>'productCode','price_version'=>'priceVersion','amount_minor'=>'amountMinor','currency'=>'currency','provider_code'=>'provider','idempotency_key'=>'idempotencyKey'] as $to=>$from)$r[$to]=$i[$from];$this->rows[$id]=$r;return ['ok'=>true,'intentId'=>$id];}
 public function startCheckout($id,$options){++$this->calls;return $this->uncertain?['ok'=>false]:['ok'=>true,'approvalUrl'=>'https://checkout.paystack.com/fixture'];}
 public function reconcileIntent($id){return ['ok'=>true];}
}
class ShopFixtureService extends shopservice {
 public $objects;
 public function __construct($objects){$this->objects=$objects;$this->init();}
 public function getObject($name,$module=''){return $this->objects[$name]??throw new RuntimeException('Unknown fixture '.$name);}
 public function uri($params,$module='', $uriMode='', $omitServerName=false, $javascriptCompatibility=false, $Strict=false){return 'https://shop.test/index.php?'.http_build_query(['module'=>$module]+$params);}
}
