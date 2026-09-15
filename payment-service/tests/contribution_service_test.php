<?php
/** Guest contribution contracts without provider/network access. @author Derek Keats */
$GLOBALS['kewl_entry_point_run']=true;
class ChisimbaObject { public $objects=[];public function getObject($name,$module=null){return $this->objects[$name];} }
class Orders {public $rows=[];public function byId($id){return $this->rows[$id]??null;}public function byToken($t){foreach($this->rows as $r)if($r['access_hash']===hash('sha256',$t))return $r;return null;}public function create($r){$this->rows[$r['id']]=$r;return true;} }
class Catalog {public $product;public function purchasable($c){return $c===$this->product['code']?$this->product:null;} }
class Config {public function getValue($k,$m){return 'TRUE';} }
class Mail {public $rows=[];public function queueEmail($m){$this->rows[$m['idempotencyKey']]=$m;return ['ok'=>true];} }
class Language {public function code2Txt($k,$m){return '{name} {product} {net} {vat} {total} {reference}';} }
require dirname(__DIR__).'/classes/contributionservice_class_inc.php';
$s=new contributionservice();$o=new Orders();$c=new Catalog();$mail=new Mail();$s->objects=['dbcontributions'=>$o,'paymentcatalogservice'=>$c,'dbsysconfig'=>new Config(),'communicationservice'=>$mail,'language'=>new Language()];$s->init();
$check=static function($v,$m){if(!$v)throw new RuntimeException($m);};
foreach([[10000,1500],[20000,3000],[50000,7500]] as [$net,$vat]){
$c->product=['code'=>'support-'.$net,'name'=>'Support','purpose_type'=>'contribution','billing_period'=>'one_off','price'=>['version_code'=>'v1','amount_minor'=>$net+$vat,'vat_minor'=>$vat,'currency'=>'ZAR']];
$t=bin2hex(random_bytes(32));$r=$s->prepare($c->product['code'],'Derek','derekkeats@gmail.com',$t);$check($r['ok'],'Guest order');$row=$r['order'];$check($row['amount_minor']===$net+$vat&&$row['vat_minor']===$vat,'VAT snapshot');$check(!str_contains(json_encode($row),$t),'Raw capability not stored');
$again=$s->prepare($c->product['code'],'Derek','derekkeats@gmail.com',$t);$check($again['order']['id']===$row['id'],'Retry must preserve order');
$v=$row+['user_id'=>null,'provider_code'=>'yoco','purpose_id'=>$row['id'],'idempotency_key'=>'contribution:'.$row['id']];$check($s->matchesIntent($v),'Canonical guest intent');$v['amount_minor']++;$check(!$s->matchesIntent($v),'Reject altered amount');$v['amount_minor']--;$v['provider_code']='fake';$check(!$s->matchesIntent($v),'No public fake fallback');
$i=['state'=>'awaiting_approval','purpose_id'=>$row['id']];$check(!$s->confirm($i)['ok']&&count($mail->rows)===($net===10000?0:($net===20000?1:2)),'Unverified return sends no receipt');$i['state']='succeeded';$s->confirm($i);$s->confirm($i);$check(isset($mail->rows['contribution-receipt:'.$row['id']]),'Receipt queued');$i['state']='refunded';$check(!$s->confirm($i)['ok'],'Refund cannot queue success receipt');
}
$check(count($mail->rows)===3,'One receipt per contribution');$check(!$s->prepare('nope','Derek','invalid',bin2hex(random_bytes(32)))['ok'],'Invalid email denied');$check($o->byToken(bin2hex(random_bytes(32)))===null,'Other browser cannot access order');
echo "PASS guest orders, all VAT totals, retries, token privacy, tamper rejection, verified-only idempotent receipts and refund guard\n";

class GuestIntents {public $row;public function byIdempotency($k){return $this->row&&$this->row['idempotency_key']===$k?$this->row:null;}public function create($r){$this->row=$r;return true;} }
class NoUserLookup {public function findByUserId($id){throw new RuntimeException('Guest checkout must not manufacture or look up a user');} }
class GuestAudit {public $last;public function append($v){$this->last=$v;return ['ok'=>true];} }
require dirname(__DIR__).'/classes/paymentservice_class_inc.php';
$core=new paymentservice();$intents=new GuestIntents();$audit=new GuestAudit();
$core->objects=['dbpaymentintents'=>$intents,'dbpaymentevents'=>new stdClass(),'dbpayments'=>new stdClass(),'dbpaymentsubscriptions'=>new stdClass(),'paymentcatalogservice'=>$c,'userservice'=>new NoUserLookup(),'accounteventservice'=>$audit,'contributionservice'=>$s];$core->init();
$input=['userId'=>null,'purposeType'=>'contribution','purposeId'=>$row['id'],'productCode'=>$row['product_code'],'priceVersion'=>$row['price_version'],'amountMinor'=>$row['amount_minor'],'currency'=>'ZAR','provider'=>'yoco','idempotencyKey'=>'contribution:'.$row['id'],'correlationId'=>'contribution:'.$row['id']];
$r=$core->createIntent($input);$check($r['ok']&&$intents->row['user_id']===null,'Core accepts canonical guest order');$check($audit->last['subjectType']==='anonymous','Guest audit identity');$check($core->createIntent($input)['intentId']===$r['intentId'],'Core intent idempotency');$input['amountMinor']++;$check(!$core->createIntent($input)['ok'],'Core rejects forged total');$input['amountMinor']--;$input['purposeType']='membership';$check(!$core->createIntent($input)['ok'],'Guest cannot create membership intent');
echo "PASS shared payment core guest intent validation and anonymous audit; no manufactured user\n";
