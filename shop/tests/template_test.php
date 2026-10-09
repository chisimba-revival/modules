<?php
/** Render every shop surface with synthetic values and strict warnings. */
require __DIR__.'/fixtures.php';
set_error_handler(static function($level,$message,$file,$line){throw new ErrorException($message,0,$level,$file,$line);});
class ShopLabels {
 private $labels=[];
 public function __construct(){foreach(file(dirname(__DIR__).'/register.conf') as $line)if(str_starts_with($line,'TEXT: ')){[$key,$desc,$value]=explode('|',substr(trim($line),6),3);$this->labels[$key]=$value;}}
 public function languageText($key,$module){return $this->labels[$key]??throw new RuntimeException('Missing label: '.$key);}
}
class ShopTemplateHelp {public function show($module,$topic,$compact){return '<button type="button">Help</button>';}}
class ShopTemplateIcons {public function render($name,$options){return '<svg width="20" height="20" aria-hidden="true" data-icon="'.$name.'"></svg>';}}
class ShopTemplateHost {
 public function getObject($name,$module=''){return $name === 'iconservice' ? new ShopTemplateIcons : new ShopTemplateHelp;}
 public function uri($p,$m){return 'https://shop.test/index.php?'.http_build_query(['module'=>$m]+$p);}
 public function render($file,$data){extract($data);ob_start();include dirname(__DIR__).'/templates/content/'.$file;return ob_get_clean();}
}
$store=new ShopMemoryStore;$payments=new ShopFixturePayments;
$s=new ShopFixtureService(['shopstore'=>$store,'paymentservice'=>$payments,'user'=>new ShopFixtureUser,'communicationservice'=>new ShopFixtureMail,'dbsysconfig'=>new ShopFixtureConfig,'altconfig'=>new ShopFixtureConfig,'language'=>new ShopLabels]);
$s->saveSettings(['revision'=>1,'bands'=>[['from'=>'1','amount'=>'110'],['from'=>'5','amount'=>'50'],['from'=>'10','amount'=>'0']],'max_quantity'=>'20','terms'=>'Fixture terms','enabled'=>'1']);
$id=str_repeat('a',32);$book=$s->saveBook(['id'=>$id,'revision'=>0,'title'=>'<script>inert</script>','price'=>'100','stock'=>'20','status'=>'published']);$cart=[$id=>5];$quote=$s->quote($cart);
$details=['name'=>'Fixture','email'=>'fixture@example.invalid','phone'=>'0123456789','address_line'=>'1 Test','city'=>'Test','province'=>'Limpopo','postal_code'=>'1234','country'=>'ZA','accept_terms'=>'1','quote_hash'=>$s->quoteHash($quote)];
$order=$s->prepare($cart,$details,str_repeat('b',64));
$data=['shopSale'=>$s->sale(),'shopService'=>$s,'shopCsrf'=>'synthetic-csrf','shopError'=>'','shopDraft'=>[],'shopBooks'=>[$book],'shopBook'=>$book,'shopCart'=>$cart,'shopQuote'=>$quote,'shopSettings'=>$s->settings(),'shopRequest'=>str_repeat('b',64),'shopAbuse'=>['issued_at'=>1,'nonce'=>'fixture','signature'=>'fixture'],'shopOrders'=>[$order],'shopOrder'=>$order,'shopToken'=>$s->token($order['id']),'shopManaged'=>false];
$host=new ShopTemplateHost;$count=0;
foreach(glob(dirname(__DIR__).'/templates/content/*_tpl.php') as $file){
 $html=$host->render(basename($file),$data);
 if(str_contains($html,'<script>inert</script>'))throw new RuntimeException('Unescaped book title');
 if(!str_contains($html,'<main'))throw new RuntimeException('Missing main landmark');
 if(str_contains($html,'method="post"')&&!str_contains($html,'name="csrf_token"'))throw new RuntimeException('Missing form token');
 ++$count;
}
$data['shopManaged']=true;$host->render('order_tpl.php',$data);
$data['shopError']='session_expired';$data['shopDraft']=$details;$host->render('error_tpl.php',$data);
$b=str_repeat('c',32);$s->saveBook(['id'=>$b,'revision'=>0,'title'=>'Second fixture','price'=>'100','stock'=>'10','status'=>'published']);
$combo=str_repeat('e',32);$s->saveBook(['id'=>$combo,'revision'=>0,'kind'=>'combo','title'=>'<script>inert</script>','price'=>'150','status'=>'published','book_ids'=>[$id,$b],'cross_sell'=>'1']);
$data['shopOffer']=$s->comboOffer([$id=>1]);$html=$host->render('cart_tpl.php',$data);
if(str_contains($html,'<script>inert</script>')||!str_contains($html,'No thanks, continue checkout')||!str_contains($html,'name="after_hash"'))throw new RuntimeException('Offer escaping/dismissal/hash');
$data['shopBook']=$s->book($combo,true);$data['shopBooks']=$s->books(true);$host->render('editor_tpl.php',$data);
echo "PASS: $count templates, registered labels, escaped content, POST tokens and retained input\n";

$data['shopManaged']=false;$data['shopError']='';
$data['shopOrder']['intent_id']='fixture-intent';
$data['shopOrder']['payment_state']='unpaid';
$html=$host->render('order_tpl.php',$data);
if(!str_contains($html,'action=reconcile'))throw new RuntimeException('Pending payment needs status check');
$data['shopOrder']['payment_state']='paid';$data['shopOrder']['fulfilment_state']='packing';
$html=$host->render('order_tpl.php',$data);
if(str_contains($html,'action=reconcile')||str_contains($html,'action=checkout')||!str_contains($html,'Payment received.'))throw new RuntimeException('Paid order must confirm payment without payment actions');
echo "PASS: pending and paid customer payment actions\n";

$data['shopOrder']['payment_state']='unpaid';
$html=$host->render('order_tpl.php',$data);
if(str_contains($html,'action=checkout')||!str_contains($html,'Confirming your payment'))throw new RuntimeException('Unconfirmed provider outcome must not invite another payment');
echo "PASS: unconfirmed payment guidance\n";

$data['shopSale']=$s->sale();$data['shopSale']['revision']=1;$data['shopSale']['title']='Saved sale QA';
$data['shopSaleEditing']=false;$data['shopDraft']=[];
$html=$host->render('sales_tpl.php',$data);
if(!str_contains($html,'Saved sale QA')||!str_contains($html,'action=editsale')||str_contains($html,'action=savesale'))throw new RuntimeException('Saved disabled sale needs explicit edit entry');
$data['shopSaleEditing']=true;$html=$host->render('sales_tpl.php',$data);
if(!str_contains($html,'value="Saved sale QA"')||!str_contains($html,'action=savesale'))throw new RuntimeException('Edit must retain saved sale');
echo "PASS: saved disabled sale summary and prefilled edit\n";

$archived=$book;$archived['id']=str_repeat('f',32);$archived['title']='Archived checkout fixture';$archived['status']='archived';
$data['shopBooks'][]=$archived;$data['shopSale']['book_ids']=[$archived['id']];
$html=$host->render('sales_tpl.php',$data);
if(str_contains($html,'Archived checkout fixture')||str_contains($html,'value="'.$archived['id'].'"'))throw new RuntimeException('Archived product offered in sale editor');
echo "PASS: archived products absent from sale options and price previews\n";

$data['shopBook']=$s->book($combo,true);
$html=$host->render('editor_tpl.php',$data);
if(str_contains($html,'Archived checkout fixture')||str_contains($html,'value="'.$archived['id'].'"'))throw new RuntimeException('Archived product offered in combo editor');
echo "PASS: archived products absent from combo choices\n";

$virtual=$s->saveBook(['id'=>str_repeat('9',32),'revision'=>0,'title'=>'Virtual contribution','price'=>'50','status'=>'published','product_type'=>'virtual','virtual_kind'=>'contribution']);
$data['shopError']='';$data['shopDraft']=[];$data['shopCart']=[$virtual['id']=>1];$data['shopQuote']=$s->quote($data['shopCart']);$data['shopOffer']=null;
$html=$host->render('cart_tpl.php',$data);
if(str_contains($html,'name="address_line"')||str_contains($html,'name="postal_code"')||str_contains($html,'name="country"')||!str_contains($html,'Contact details'))throw new RuntimeException('Virtual checkout must omit delivery controls');
$data['shopCart'][$id]=1;$data['shopQuote']=$s->quote($data['shopCart']);$html=$host->render('cart_tpl.php',$data);
if(!str_contains($html,'name="address_line"')||!str_contains($html,'Delivery details'))throw new RuntimeException('Mixed checkout retains address fields');
$data['shopBooks']=[$virtual];$html=$host->render('catalogue_tpl.php',$data);
if(str_contains($html,'Out of stock')||!str_contains($html,'action=add'))throw new RuntimeException('Virtual products need purchase controls without stock');
$data['shopBook']=$virtual;$html=$host->render('editor_tpl.php',$data);
if(!str_contains($html,'value="virtual" selected')||!str_contains($html,'policy=download'))throw new RuntimeException('Virtual editor or shared download picker missing');
echo "PASS: virtual editor, download picker, stock-free buying and virtual/mixed checkout fields.\n";
$html=$host->render('catalogue_tpl.php',$data);
if(str_contains($html,'name="buy_now"'))throw new RuntimeException('Buy now must default off');
$settings=$s->settings();$s->saveSettings(['revision'=>$settings['revision'],'max_quantity'=>'100','terms'=>'Test terms','enabled'=>'1','buy_now'=>'1']);
$html=$host->render('catalogue_tpl.php',$data);
if(!str_contains($html,'name="buy_now" value="1"')||!str_contains($html,'Buy now')||!str_contains($html,'Add to cart'))throw new RuntimeException('Both purchase choices required when enabled');
$html=$host->render('cart_tpl.php',$data);if(!str_contains($html,'id="shop-checkout"'))throw new RuntimeException('Checkout shortcut destination missing');
$sorted=$s->saveBook(['id'=>str_repeat('8',32),'revision'=>0,'title'=>'Ordered product','price'=>'115','stock'=>'1','display_order'=>'10','status'=>'published']);
if($sorted['display_order']!==10)throw new RuntimeException('Order not persisted');
try{$s->saveBook(['id'=>str_repeat('7',32),'revision'=>0,'title'=>'Invalid order','price'=>'115','stock'=>'1','display_order'=>'-1','status'=>'published']);throw new RuntimeException('Negative order accepted');}catch(DomainException $e){}
echo "PASS: Buy now defaults off, enabled controls retain cart choice, checkout target, display order validation.\n";
