<?php
/** Physical compatibility and virtual fulfilment boundaries; no provider calls or mail delivery. */
require __DIR__.'/fixtures.php';
require dirname(__DIR__).'/classes/shopdownloads_class_inc.php';
require dirname(__DIR__,3).'/framework/app/core_modules/filemanager/classes/filedelivery_class_inc.php';
set_error_handler(static function($level,$message,$file,$line){throw new ErrorException($message,0,$level,$file,$line);});
function check($ok,$message){if(!$ok)throw new RuntimeException($message);}
function reject($fn,$code){try{$fn();throw new RuntimeException('Accepted '.$code);}catch(DomainException $e){check($e->getMessage()===$code,'Expected '.$code.', got '.$e->getMessage());}}
$root=sys_get_temp_dir().'/shop-virtual-'.bin2hex(random_bytes(5));mkdir($root);mkdir($root.'/private');mkdir($root.'/public');file_put_contents($root.'/private/fixture.pdf','synthetic paid file');
$config=new class($root) extends ShopFixtureConfig{function __construct(public $root){}function getValue($k,$m){return $k==='SECUREFODLER'?$this->root.'/private':parent::getValue($k,$m);}function getcontentBasePath(){return $this->root.'/public';}};
$user=new class extends ShopFixtureUser{public $logged=true,$id='fixture-manager';function isLoggedIn(){return $this->logged;}function userId(){return $this->logged?$this->id:'';}};
$store=new ShopMemoryStore;$payments=new ShopFixturePayments;$mail=new ShopFixtureMail;
$files=new class{public $file=['id'=>'fixture-file','filefolder'=>'users/fixture-manager','access'=>'private_selected','path'=>'fixture.pdf','filename'=>'Fixture.pdf'];function getFile($id){return $id==='fixture-file'?$this->file:null;}};
$policy=new class{public $allowed=true;function mayRead($file){return $this->allowed;}};
$catalogue=new class{public $products=[],$prices=[];function createProduct($input){if($input['purposeId']!=='tier_1')return ['ok'=>false];$this->products[$input['code']]=$input;return ['ok'=>true,'productId'=>$input['code']];}function addPrice($id,$input){$this->prices[$id]=$input;return ['ok'=>true];}};
$s=new ShopFixtureService(['shopstore'=>$store,'paymentservice'=>$payments,'user'=>$user,'communicationservice'=>$mail,'dbsysconfig'=>$config,'altconfig'=>$config,'language'=>new ShopFixtureLanguage,'paymentcatalogservice'=>$catalogue]);
$downloads=new class extends shopdownloads{public $objects;function getObject($n,$m=''){return $this->objects[$n];}};
$downloads->objects=['dbfile'=>$files,'dbsysconfig'=>$config,'altconfig'=>$config,'filereadpolicy'=>$policy,'shopservice'=>$s];$s->objects['shopdownloads']=$downloads;
try {
$s->saveSettings(['revision'=>1,'bands'=>[],'max_quantity'=>'100','terms'=>'Synthetic terms','enabled'=>'1']);
$book=$s->saveBook(['id'=>str_repeat('a',32),'revision'=>0,'title'=>'Legacy book','price'=>'100','stock'=>'5','status'=>'published']);check(ShopRules::physical($book),'Legacy input defaults physical');
$base=['revision'=>0,'product_type'=>'virtual','price'=>'50','status'=>'published'];
$contribution=$s->saveBook($base+['id'=>str_repeat('b',32),'title'=>'Support','virtual_kind'=>'contribution']);
$download=$s->saveBook($base+['id'=>str_repeat('c',32),'title'=>'Paid guide','virtual_kind'=>'download','download_files'=>'fixture-file']);
$membership=$s->saveBook($base+['id'=>str_repeat('d',32),'title'=>'Tier 1 monthly','virtual_kind'=>'membership','billing_period'=>'monthly','membership_tier'=>'tier_1']);
check(count($catalogue->products)===1 && $catalogue->products[$membership['virtual']['payment_code']]['durationMonths']===1,'Monthly canonical product created');
check($s->membershipProductAvailable($membership['virtual']['payment_code']),'Published membership available');
$updated=$s->saveBook(['id'=>$membership['id'],'revision'=>1,'title'=>'Tier 1 annual','price'=>'500','status'=>'published','product_type'=>'virtual','virtual_kind'=>'membership','billing_period'=>'annual','membership_tier'=>'tier_1']);
check(!$s->membershipProductAvailable($membership['virtual']['payment_code'])&&$s->membershipProductAvailable($updated['virtual']['payment_code']),'Retired price unavailable for new checkout');
check($catalogue->products[$updated['virtual']['payment_code']]['durationMonths']===12 && count($catalogue->prices)===2,'Annual offer and immutable prior price');
reject(fn()=>$s->quote([$membership['id']=>1]),'membership_separate');
reject(fn()=>$s->addFromPage([],$membership['id']),'membership_separate');
reject(fn()=>$s->quote([$download['id']=>2]),'download_quantity');
reject(fn()=>$s->saveBook($base+['id'=>str_repeat('e',32),'title'=>'Wrong recurrence','virtual_kind'=>'contribution','billing_period'=>'monthly']),'invalid_billing_period');
$quote=$s->quote([$contribution['id']=>2,$download['id']=>1]);check($quote['quantity']===0&&$quote['shipping_minor']===0&&ShopRules::inventory($quote)===[],'Virtual cart ignores shipping configuration and stock');
$details=['name'=>'Buyer','email'=>'buyer@example.invalid','accept_terms'=>'1','quote_hash'=>$s->quoteHash($quote)];
$user->logged=false;reject(fn()=>$s->prepare([$contribution['id']=>2,$download['id']=>1],$details,str_repeat('a',64)),'download_login');
$guestQuote=$s->quote([$contribution['id']=>1]);$guest=$s->prepare([$contribution['id']=>1],array_replace($details,['quote_hash'=>$s->quoteHash($guestQuote)]),str_repeat('b',64));check($guest['user_id']===null&&json_decode($guest['address_json'],true)===['name'=>'Buyer'],'Guest contribution has no delivery fields');
$user->logged=true;$user->admin=false;$user->id='buyer';
$order=$s->prepare([$contribution['id']=>2,$download['id']=>1],$details,str_repeat('c',64));check($order['user_id']==='buyer','Account comes from authenticated identity');
reject(fn()=>$s->downloadOrder($order['id'],'fixture-file'),'forbidden');
$s->checkout($s->token($order['id']));$order=$store->one('orders',$order['id']);$intent=$payments->rows[$order['intent_id']];$intent['state']='succeeded';$payments->rows[$intent['id']]=$intent;
$s->fulfil($intent);$s->fulfil($intent);$paid=$store->one('orders',$order['id']);
check($paid['fulfilment_state']==='complete'&&$store->one('books',$book['id'])['stock']===5,'Virtual payment completes without touching physical stock');
check(count($mail->rows)===1,'Receipt replay is idempotent');
check($downloads->mayDownloadFile($files->file,$order['id']),'Verified purchaser may download');
check(count($s->myPurchases())===1,'Account lists only own orders');
$user->id='outsider';check(!$downloads->mayDownloadFile($files->file,$order['id'])&&$s->myPurchases()===[],'Another account cannot download or list order');
$user->logged=false;check(!$downloads->mayDownloadFile($files->file,$order['id']),'Anonymous private-link holder denied');
$user->logged=true;$user->id='buyer';reject(fn()=>$s->downloadOrder($order['id'],'another-file'),'forbidden');
$files->file['access']='public';check(!$downloads->mayDownloadFile($files->file,$order['id']),'Public source refused');$files->file['access']='private_selected';
file_put_contents($root.'/public/fixture.pdf','public copy');check(!$downloads->mayDownloadFile($files->file,$order['id']),'Exposed original refused');unlink($root.'/public/fixture.pdf');
$files->file['filefolder']='context/private';check(!$downloads->mayDownloadFile($files->file,$order['id']),'Course material refused');$files->file['filefolder']='users/fixture-manager';
$intent['state']='refunded';$payments->rows[$intent['id']]=$intent;check(!$downloads->mayDownloadFile($files->file,$order['id']),'Canonical refund denies before order callback');$s->reverse($intent);check(!$downloads->mayDownloadFile($files->file,$order['id']),'Refunded order denied');
$user->admin=true;$user->id='fixture-manager';
$s->saveSettings(['revision'=>2,'bands'=>[['from'=>'1','amount'=>'100'],['from'=>'2','amount'=>'50']],'max_quantity'=>'100','terms'=>'Synthetic terms','enabled'=>'1']);
$mixed=$s->quote([$book['id']=>1,$contribution['id']=>5,$download['id']=>1]);check($mixed['quantity']===1&&$mixed['shipping_minor']===10000,'Virtual quantity never reduces physical delivery rate');
check(count(ShopRules::inventory($mixed))===1,'Mixed inventory only physical');
$physical=$s->quote([$book['id']=>2]);check($physical['shipping_minor']===5000,'Existing physical bands unchanged');
reject(fn()=>$s->saveBook(['id'=>str_repeat('f',32),'revision'=>0,'kind'=>'combo','title'=>'Invalid combo','price'=>'100','status'=>'published','book_ids'=>[$book['id'],$download['id']]]),'invalid_combo');
$mixedDetails=['name'=>'Mixed buyer','email'=>'mixed@example.invalid','phone'=>'0123456789','address_line'=>'1 Test Street','city'=>'Test City','province'=>'Gauteng','postal_code'=>'1234','country'=>'ZA','accept_terms'=>'1','quote_hash'=>$s->quoteHash($mixed)];
$mixedOrder=$s->prepare([$book['id']=>1,$contribution['id']=>5,$download['id']=>1],$mixedDetails,str_repeat('d',64));
$s->checkout($s->token($mixedOrder['id']));$mixedOrder=$store->one('orders',$mixedOrder['id']);$mixedIntent=$payments->rows[$mixedOrder['intent_id']];$mixedIntent['state']='succeeded';$payments->rows[$mixedIntent['id']]=$mixedIntent;
$s->fulfil($mixedIntent);check($store->one('orders',$mixedOrder['id'])['fulfilment_state']==='packing'&&$store->one('books',$book['id'])['stock']===4,'Mixed payment packs physical goods and deducts only physical stock');
check($downloads->mayDownloadFile($files->file,$mixedOrder['id']),'Mixed download is available before physical dispatch');
$s->archiveBook(['id'=>$download['id'],'revision'=>$download['revision']]);check($downloads->mayDownloadFile($files->file,$mixedOrder['id']),'Archiving stops new sales without removing purchased downloads');
$legacy=['lines'=>[['book_id'=>$book['id'],'title'=>'Legacy','quantity'=>2]]];check(ShopRules::inventory($legacy)[0]['quantity']===2,'Old order snapshots remain physical');
echo "PASS: physical defaults, virtual/mixed shipping, stock, authenticated downloads, guest contributions, membership prices, recurrence, refunds, replay and secure file boundaries.\n";
} finally {unlink($root.'/private/fixture.pdf');rmdir($root.'/private');rmdir($root.'/public');rmdir($root);}
