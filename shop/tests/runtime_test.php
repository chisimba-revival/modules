<?php
/** Local-only real database tests. Fixtures replace every payment/email boundary. */
if (PHP_SAPI!=='cli'||getenv('SHOP_TEST_SITE')!=='/var/www/html/ch') exit(64);
chdir(getenv('SHOP_TEST_SITE'));
$x=simplexml_load_file('config/config.xml');
if (!in_array(parse_url((string)$x->KEWL_SITE_ROOT,PHP_URL_HOST),['chisimba.test','localhost','127.0.0.1'],true)) exit(64);
$_SERVER['REQUEST_METHOD']='CLI';$_SERVER['HTTP_HOST']='chisimba.test';$_SERVER['SCRIPT_NAME']='/ch/index.php';$_SERVER['QUERY_STRING']='';
$GLOBALS['kewl_entry_point_run']=true;require 'classes/core/engine_class_inc.php';$engine=new engine();
$engine->loadClass('shopservice','shop');
require __DIR__.'/fixtures.php';
$store=$engine->getObject('shopstore','shop');$db=$engine->getDbObj();
$user=new ShopFixtureUser;$mail=new ShopFixtureMail;$payments=new ShopFixturePayments;
$s=new ShopFixtureService(['shopstore'=>$store,'paymentservice'=>$payments,'user'=>$user,'communicationservice'=>$mail,'dbsysconfig'=>new ShopFixtureConfig,'altconfig'=>new ShopFixtureConfig,'language'=>new ShopFixtureLanguage]);
$details=['name'=>'Shop Fixture','email'=>'shop-fixture@example.invalid','phone'=>'0123456789','address_line'=>'1 Synthetic Street','city'=>'Fixture City','province'=>'Limpopo','postal_code'=>'1234','country'=>'ZA','accept_terms'=>'1'];
if (($argv[1]??'')==='--race') {
    try {$cart=[$argv[2]=>1];$s->prepare($cart,$details+['quote_hash'=>$s->quoteHash($s->quote($cart))],bin2hex(random_bytes(32)));echo "RESERVED\n";}catch(DomainException $e){echo $e->getMessage()."\n";}exit;
}
$assert=static function($ok,$message){if(!$ok)throw new RuntimeException($message);};
$original=$store->one('settings','shop');$ids=[];
$assert(!$store->rows('orders')&&!$store->rows('books'),'Runtime test requires an empty local shop');
try {
    $s->saveSettings(['revision'=>$original['revision'],'bands'=>[['from'=>'1','amount'=>'110'],['from'=>'5','amount'=>'50'],['from'=>'10','amount'=>'0']],'max_quantity'=>'20','terms'=>'Synthetic test only','enabled'=>'1']);
    $id=bin2hex(random_bytes(16));$ids[]=$id;
    $s->saveBook(['id'=>$id,'revision'=>0,'title'=>'Disposable Shop QA','price'=>'100','stock'=>'1','status'=>'published']);
    $jobs=[];
    for($i=0;$i<2;$i++){$pipes=[];$p=proc_open([PHP_BINARY,__FILE__,'--race',$id],[1=>['pipe','w'],2=>['pipe','w']],$pipes);$jobs[]=[$p,$pipes];}
    $outputs=[];
    foreach($jobs as [$p,$pipes]){$outputs[]=trim(stream_get_contents($pipes[1]));$errors=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);$assert(proc_close($p)===0,'Concurrent request failed: '.$errors);}
    sort($outputs);$assert($outputs===['RESERVED','out_of_stock'],'Last copy must only be reserved once: '.json_encode($outputs));
    $orders=$store->rows('orders');$assert(count($orders)===1,'One order after race');$o=$orders[0];
    $token=$s->token($o['id']);$s->checkout($token);$o=$s->order($token);$intent=$payments->rows[$o['intent_id']];$intent['state']='succeeded';$payments->rows[$intent['id']]=$intent;
    $s->fulfil($intent);$s->fulfil($intent);
    $assert((int)$store->one('books',$id)['stock']===0,'Stock deducted once');
    $assert(count($mail->rows)===1,'Only one confirmation');
    $paid=$s->order($token);$s->dispatchOrder(['id'=>$o['id'],'revision'=>$paid['revision'],'courier'=>'Fixture Courier','tracking'=>'QA-ONLY']);
    $assert(count($mail->rows)===2,'Dispatch email queued into fixture');
    $intent['state']='refunded';$s->reverse($intent);$s->reverse($intent);
    $assert((int)$store->one('books',$id)['stock']===0,'No automatic restock on refund');
    $assert($s->order($token)['fulfilment_state']==='dispatched','Dispatch preserved after refund');
    $before=$store->one('books',$id);
    try{$store->transaction(function()use($store,$id){$store->save('books',$id,['stock'=>999]);throw new DomainException('rollback');});}catch(DomainException $e){}
    $assert($store->one('books',$id)===$before,'Transaction rolls back writes');
    echo "PASS: installed database, concurrent last-copy reservations, duplicate payment, dispatch, refund and rollback\n";
} finally {
    foreach($store->rows('orders') as $o) {
        $q=json_decode($o['snapshot'],true);if(!in_array($q['lines'][0]['book_id']??'',$ids,true))continue;
        $oid=$db->quote($o['id'],'text');$db->exec('DELETE FROM tbl_shop_history WHERE order_id='.$oid);$db->exec('DELETE FROM tbl_shop_orders WHERE id='.$oid);
    }
    foreach($ids as $id)$db->exec('DELETE FROM tbl_shop_books WHERE id='.$db->quote($id,'text'));
    $store->save('settings','shop',['settings_json'=>$original['settings_json'],'revision'=>$original['revision']]);
    echo "PASS: fixtures removed and original disabled settings restored\n";
}
