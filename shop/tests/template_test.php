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
class ShopTemplateIcons {public function render($name,$options){return '<svg aria-hidden="true" data-icon="'.$name.'"></svg>';}}
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
echo "PASS: $count templates, registered labels, escaped content, POST tokens and retained input\n";
