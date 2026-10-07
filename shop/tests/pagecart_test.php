<?php
require __DIR__.'/fixtures.php';
$store=new ShopMemoryStore;$s=new ShopFixtureService(['shopstore'=>$store,'paymentservice'=>new ShopFixturePayments,'user'=>new ShopFixtureUser,'altconfig'=>new ShopFixtureConfig]);
$id=str_repeat('a',32);$s->saveBook(['id'=>$id,'revision'=>0,'title'=>'Page fixture','price'=>'100','stock'=>'2','status'=>'published']);
function check($v,$m){if(!$v)throw new RuntimeException($m);}
check($s->addFromPage([],$id)===[$id=>1],'Add without configured shipping');
check($s->addFromPage([$id=>1],$id)===[$id=>2],'Adds one copy');
try{$s->addFromPage([$id=>2],$id);throw new RuntimeException('Accepted out of stock');}catch(DomainException $e){check($e->getMessage()==='out_of_stock','Stock validation');}
$store->save('books',$id,['status'=>'draft']);
try{$s->addFromPage([],$id);throw new RuntimeException('Accepted draft');}catch(DomainException $e){check($e->getMessage()==='book_unavailable','Public availability');}
foreach(['//evil.test','https://evil.test',"/\\evil.test","/x\r\nLocation: evil"] as $url)check(ShopRules::returnPath($url)==='/','Unsafe return rejected');
check(ShopRules::returnPath('/index.php?module=sitepages&slug=home#old')==='/index.php?module=sitepages&slug=home','Local return retained');
echo "PASS: page cart addition, stock, drafts and local redirect validation\n";
