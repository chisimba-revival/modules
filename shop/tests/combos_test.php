<?php
/** Combo inventory and cross-sell regressions. No external payments or email. */
require __DIR__.'/fixtures.php';
$store=new ShopMemoryStore;$payments=new ShopFixturePayments;$user=new ShopFixtureUser;$mail=new ShopFixtureMail;$config=new ShopFixtureConfig;
$s=new ShopFixtureService(['shopstore'=>$store,'paymentservice'=>$payments,'user'=>$user,'communicationservice'=>$mail,'dbsysconfig'=>$config,'altconfig'=>$config,'language'=>new ShopFixtureLanguage]);
$n=0;$ok=function($value,$message)use(&$n){++$n;if(!$value)throw new RuntimeException($message);};
$reject=function($fn,$reason)use($ok){try{$fn();throw new RuntimeException('Expected '.$reason);}catch(DomainException $e){$ok($e->getMessage()===$reason,$e->getMessage());}};
$s->saveSettings(['revision'=>1,'bands'=>[['from'=>'1','amount'=>'110'],['from'=>'3','amount'=>'50']],'max_quantity'=>'100','terms'=>'Synthetic terms','enabled'=>'1']);
$ids=[];foreach(['a','b','c'] as $char){$id=str_repeat($char,32);$ids[]=$id;$s->saveBook(['id'=>$id,'revision'=>0,'title'=>'Book '.$char,'price'=>'100','stock'=>'10','status'=>'published']);}
$combo=str_repeat('e',32);$input=['id'=>$combo,'revision'=>0,'kind'=>'combo','title'=>'Three books','price'=>'250','status'=>'published','book_ids'=>$ids,'cross_sell'=>'1'];
$user->admin=false;$reject(fn()=>$s->saveBook($input),'forbidden');$user->admin=true;
$reject(fn()=>$s->saveBook(array_replace($input,['book_ids'=>[$ids[0],$ids[0]]])),'invalid_combo');
$book=$s->saveBook($input);$ok($book['stock']===10&&$book['saving_minor']===5000,'Derived stock and SAVE');
$q=$s->quote([$combo=>2,$ids[0]=>1]);$ok($q['quantity']===7&&$q['shipping_minor']===5000,'Shipping counts actual books');
$physical=array_column(ShopRules::inventory($q),'quantity','book_id');$ok($physical===[$ids[0]=>3,$ids[1]=>2,$ids[2]=>2],'Aggregate overlaps');
$ok(!$s->comboOffer([$combo=>1]),'Already in basket');$ok(!$s->comboOffer(array_fill_keys($ids,1)),'All components already present');
$offer=$s->comboOffer([$ids[0]=>2]);$ok($offer['cart']===[$ids[0]=>1,$combo=>1],'Only one matching copy replaced');
$ok($offer['shipping_saving_minor']===6000&&$offer['extra_minor']===9000,'Exact difference includes shipping');
$upgraded=$s->acceptCombo([$ids[0]=>2],$combo,$offer['before_hash'],$s->quoteHash($offer['quote']));$ok($upgraded===$offer['cart'],'Accept revalidates');
$reject(fn()=>$s->acceptCombo([$ids[0]=>1],$combo,$offer['before_hash'],$s->quoteHash($offer['quote'])),'offer_changed');
$other=str_repeat('f',32);$s->saveBook(array_replace($input,['id'=>$other,'book_ids'=>[$ids[1],$ids[2]],'price'=>'180']));
$ok(!$s->comboOffer([$ids[0]=>1,$other=>1],$combo),'Existing combo overlapping candidate suppressed');
$reject(fn()=>$s->saveBook(array_replace($input,['id'=>str_repeat('d',32),'book_ids'=>[$ids[0],$combo]])),'invalid_combo');
$store->save('books',$ids[2],['stock'=>0]);$ok(!$s->comboOffer([$ids[0]=>1],$combo),'Out of stock excluded');$store->save('books',$ids[2],['stock'=>10]);
$store->save('books',$ids[2],['status'=>'draft']);$reject(fn()=>$s->quote([$combo=>1]),'book_unavailable');$store->save('books',$ids[2],['status'=>'published']);
$fmt=fn($at)=>(new DateTimeImmutable('@'.$at))->setTimezone(new DateTimeZone('Africa/Johannesburg'))->format('Y-m-d\TH:i');
$s->saveSale(['revision'=>0,'enabled'=>'1','title'=>'Synthetic sale','description'=>'','percent'=>'40','book_ids'=>$ids,'starts_at'=>$fmt(time()-300),'ends_at'=>$fmt(time()+3600),'button_label'=>'Shop']);
$ok($s->book($combo)['sale_price_minor']===null&&$s->book($combo)['saving_minor']===0,'Combo fixed; no misleading saving versus sale books');$ok(!$s->comboOffer([$ids[0]=>1],$combo),'No offer when worse than individual prices');$s->stopSale(['revision'=>1]);
$details=['name'=>'Fixture','email'=>'fixture@example.invalid','phone'=>'0123456789','address_line'=>'1 Test','city'=>'Test','province'=>'Gauteng','postal_code'=>'1234','country'=>'ZA','accept_terms'=>'1'];
$cart=[$combo=>2,$ids[0]=>1];$order=$s->prepare($cart,$details+['quote_hash'=>$s->quoteHash($s->quote($cart))],str_repeat('1',64));
$ok($store->reserved($ids[0],time())===3&&$store->reserved($ids[1],time())===2,'Reservations include combo and individual copies');
// Change definition after reservation: fulfilment must honour the original contents.
$s->saveBook(array_replace($input,['revision'=>1,'book_ids'=>[$ids[0],$ids[1]],'price'=>'175']));
$token=$s->token($order['id']);$s->checkout($token);$order=$s->order($token);$intent=$payments->rows[$order['intent_id']];$intent['state']='succeeded';$payments->rows[$intent['id']]=$intent;$s->fulfil($intent);$s->fulfil($intent);
$ok($store->one('books',$ids[0])['stock']===7&&$store->one('books',$ids[1])['stock']===8&&$store->one('books',$ids[2])['stock']===8,'Immutable contents deducted once');
$intent['state']='refunded';$s->reverse($intent);$order=$s->order($token);$s->updateFulfilment(['id'=>$order['id'],'revision'=>$order['revision'],'operation'=>'restock']);
$ok($store->one('books',$ids[0])['stock']===10&&$store->one('books',$ids[2])['stock']===10,'Refund restock uses original contents');
$reject(fn()=>$s->quote([$combo=>51]),'invalid_number');
echo "PASS: $n combo pricing, overlap, stock, reservation, fulfilment, shipping and stale-offer checks\n";
