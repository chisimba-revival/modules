<?php
require __DIR__.'/fixtures.php';
$store=new ShopMemoryStore;$user=new ShopFixtureUser;$payments=new ShopFixturePayments;
$s=new ShopFixtureService(['shopstore'=>$store,'paymentservice'=>$payments,'user'=>$user,'altconfig'=>new ShopFixtureConfig]);
function check($ok,$message){if(!$ok)throw new RuntimeException($message);}
function rejects($fn,$message){try{$fn();}catch(DomainException $e){check($e->getMessage()===$message,'Wrong error: '.$e->getMessage());return;}throw new RuntimeException('Expected '.$message);}
$id=str_repeat('a',32);$other=str_repeat('b',32);
foreach([$id,$other] as $key)$s->saveBook(['id'=>$key,'revision'=>0,'title'=>'Fixture','price'=>'185','stock'=>'10','status'=>'published']);
$now=time();$format=fn($n)=>(new DateTimeImmutable('@'.$n))->setTimezone(new DateTimeZone('Africa/Johannesburg'))->format('Y-m-d\TH:i');
$input=['revision'=>0,'title'=>'Opening sale','description'=>'Fixture','percent'=>'40','book_ids'=>[$id],'starts_at'=>$format($now-3600),'ends_at'=>$format($now+3600),'enabled'=>'1','button_label'=>'Shop the sale'];
$user->admin=false;rejects(fn()=>$s->saveSale($input),'forbidden');$user->admin=true;
$sale=$s->saveSale($input);check($sale['revision']===1,'Save revision');
check($s->book($id)['sale_price_minor']===11100,'40 percent off R185 is R111');
check($s->book($other)['sale_price_minor']===null,'Unselected book unchanged');
check($store->one('books',$id)['price_minor']===18500,'Regular price preserved');
rejects(fn()=>$s->saveSale($input),'changed_elsewhere');
$draft=$store->one('books',$id);$draft['status']='draft';check(ShopSaleRules::price($draft,$sale,$now)===null,'Drafts excluded');
$book=$store->one('books',$id);
check(ShopSaleRules::price($book,$sale,$sale['starts_at']-1)===null,'Not early');
check(ShopSaleRules::price($book,$sale,$sale['starts_at'])===11100,'Inclusive start');
check(ShopSaleRules::price($book,$sale,$sale['ends_at'])===null,'Exclusive end');
$odd=$book;$odd['price_minor']=19999;check(ShopSaleRules::price($odd,$sale,$now)===11999,'Half-up cent rounding');
$policy=['revision'=>1,'zones'=>['ZA'=>['enabled'=>true,'max_quantity'=>100,'bands'=>[['from'=>1,'amount_minor'=>11000]]]]];
$quote=ShopRules::quote([$id=>2],[$id=>$s->book($id)],'ZA',$policy);check($quote['subtotal_minor']===22200&&$quote['amount_minor']===33200,'Sale total and unchanged shipping');
check($quote['lines'][0]['regular_unit_minor']===18500&&$quote['lines'][0]['sale_revision']===1,'Auditable regular/sale snapshot');
$user->admin=false;rejects(fn()=>$s->stopSale(['revision'=>1]),'forbidden');$user->admin=true;
$s->stopSale(['revision'=>1]);check($s->book($id)['sale_price_minor']===null,'Manual stop');
$after=ShopRules::quote([$id=>2],[$id=>$s->book($id)],'ZA',$policy);check($s->quoteHash($quote)!==$s->quoteHash($after),'Stale basket requires price review');
check($quote['amount_minor']===33200,'Existing quote immutable');
rejects(fn()=>ShopSaleRules::timestamp('2026-02-30T12:00'),'invalid_sale_dates');
rejects(fn()=>$s->saveSale(array_replace($input,['revision'=>2,'percent'=>'100'])),'invalid_number');
rejects(fn()=>$s->saveSale(array_replace($input,['revision'=>2,'image_url'=>'https://evil.test/cover.jpg'])),'invalid_image');
check($store->one('settings','shop')['revision']===1,'Sale does not rewrite shipping settings');
echo "PASS: sale permissions, selections, scheduled boundaries, rounding, snapshots, stale prices, stop and regular-price preservation\n";

$store->save('books',$other,['status'=>'archived']);
rejects(fn()=>$s->saveSale(array_replace($input,['revision'=>2,'book_ids'=>[$other]])),'invalid_sale_books');
$archived=$store->one('books',$other);
check(ShopSaleRules::price($archived,array_replace($sale,['book_ids'=>[$other]]),$now)===null,'Archived book never receives actual sale price');
echo "PASS: archived products rejected from promotions\n";
