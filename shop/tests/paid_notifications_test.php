<?php
/** Paid-order merchant notices: synthetic recipients and no network access. */
require_once __DIR__.'/fixtures.php';
$store=$GLOBALS['shopNotificationTestStore']??new ShopMemoryStore; $payments=new ShopFixturePayments; $user=new ShopFixtureUser;
$mail=new class extends ShopFixtureMail {
    public $failRecipient='';
    public function queueEmail($message) {
        if ($message['to']===$this->failRecipient) return ['ok'=>false];
        return parent::queueEmail($message);
    }
};
$config=new ShopFixtureConfig;
$s=new ShopFixtureService(['shopstore'=>$store,'paymentservice'=>$payments,'user'=>$user,'communicationservice'=>$mail,'altconfig'=>$config,'dbsysconfig'=>$config,'language'=>new ShopFixtureLanguage]);
$count=0;
$assert=function($ok,$message)use(&$count){++$count;if(!$ok)throw new RuntimeException($message);};
$save=function($recipients)use($s){return $s->saveSettings(['revision'=>$s->settings()['revision'],'max_quantity'=>'20','terms'=>'Fixture terms','enabled'=>'1','bands'=>[['from'=>'1','amount'=>'10']],'paid_notification_recipients'=>$recipients]);};
$assert($s->settings()['paid_notification_recipients']===[],'Upgrade defaults disabled');
$settings=$save(" OWNER@example.invalid\nowner@example.invalid, books@example.invalid; ");
$assert($settings['paid_notification_recipients']===['owner@example.invalid','books@example.invalid'],'Normalise and deduplicate');
foreach (['invalid',"owner@example.invalid\r\nBcc: victim@example.invalid",['owner@example.invalid'],implode(',',array_map(fn($n)=>"person$n@example.invalid",range(1,21)))] as $bad) {
    $before=$s->settings();
    try {$save($bad);throw new LogicException('Expected invalid recipients');}
    catch(DomainException $e){$assert($e->getMessage()==='invalid_notification_recipients','Useful validation error');}
    $assert($s->settings()===$before,'Invalid input preserves saved settings');
}
$user->admin=false;
try {$save('outsider@example.invalid');throw new LogicException('Expected forbidden');}
catch(DomainException $e){$assert($e->getMessage()==='forbidden','Only manager configures recipients');}
$user->admin=true;
$id=str_repeat('a',32);
$s->saveBook(['id'=>$id,'revision'=>0,'title'=>'Fixture Book','price'=>'150','stock'=>'10','status'=>'published']);
$make=function()use($s,$payments,$id){
    $cart=[$id=>1];
    $details=['name'=>'Fixture Buyer','email'=>'buyer@example.invalid','phone'=>'0123456789','address_line'=>'1 Fixture Road','suburb'=>'','city'=>'Fixture City','province'=>'Limpopo','postal_code'=>'1234','country'=>'ZA','accept_terms'=>'1','quote_hash'=>$s->quoteHash($s->quote($cart))];
    $order=$s->prepare($cart,$details,bin2hex(random_bytes(32)));
    $token=$s->token($order['id']);$s->checkout($token);$order=$s->order($token);
    return $payments->rows[$order['intent_id']];
};
$intent=$make();
$assert(!$s->fulfil($intent)['ok'] && !$mail->rows,'Unverified payment sends nothing');
$intent['state']='succeeded';$payments->rows[$intent['id']]=$intent;
$bad=$intent;$bad['amount_minor']++;
$assert(!$s->fulfil($bad)['ok'] && !$mail->rows,'Mismatched payment sends nothing');
$mail->failRecipient='books@example.invalid';
try {$s->fulfil($intent);throw new LogicException('Expected queue failure');}
catch(RuntimeException $e){$assert($e->getMessage()==='Shop notice could not be queued','Partial queue failure remains retryable');}
$assert(count($mail->rows)===2,'Customer and first recipient queued before failure');
$assert($store->one('orders',$intent['purpose_id'])['payment_state']==='paid','Queue failure preserves paid state');
$assert((int)$store->one('books',$id)['stock']===9,'Stock deducted once');
$save('replacement@example.invalid');$mail->failRecipient='';
$s->retryNotice($intent['purpose_id']);$s->fulfil($intent);
$assert(count($mail->rows)===3,'Retries queue each original recipient once');
$assert((int)$store->one('books',$id)['stock']===9,'Retry does not deduct stock');
$merchant=array_values(array_filter($mail->rows,fn($m)=>($m['metadata']['notice']??'')==='merchant_paid'));
$assert(array_column($merchant,'to')===['owner@example.invalid','books@example.invalid'],'Retry uses captured recipients');
foreach($merchant as $message){
    $assert(str_contains($message['text'],'action=operation') && str_contains($message['text'],'id='.$intent['purpose_id']) && !str_contains($message['text'],'token='),'Authenticated manager link only');
    $assert(str_contains($message['text'],'Fixture Book') && str_contains($message['text'],'R160.00'),'Items and total included');
    $assert(!isset($message['cc']) && !isset($message['bcc']),'Separate private deliveries');
}
// Legacy paid orders must not receive new merchant notices during confirmation retries.
$order=$store->one('orders',$intent['purpose_id']);$snapshot=json_decode($order['snapshot'],true);
unset($snapshot['paid_notification_recipients']);$store->save('orders',$order['id'],['snapshot'=>json_encode($snapshot)]);
$s->retryNotice($order['id']);$assert(count($mail->rows)===3,'Legacy paid order does not acquire new recipients');
$save('');$disabled=$make();$disabled['state']='succeeded';$payments->rows[$disabled['id']]=$disabled;$s->fulfil($disabled);
$assert(count($mail->rows)===4,'Disabled notifications preserve customer confirmation');
$save('new@example.invalid');$s->fulfil($disabled);$assert(count($mail->rows)===4,'Previously disabled paid order stays disabled');
$review=$make();$store->save('books',$id,['stock'=>0]);$review['state']='succeeded';$payments->rows[$review['id']]=$review;$s->fulfil($review);
$last=end($mail->rows);
$assert($last['to']==='new@example.invalid' && str_contains($last['text'],'mod_shop_paid_review'),'Late paid order notifies recipient of review requirement');
echo "PASS: $count paid-order recipient, verification, retry and privacy checks\n";
