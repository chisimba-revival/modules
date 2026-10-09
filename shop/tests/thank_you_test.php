<?php
require __DIR__.'/fixtures.php';
$store=new ShopMemoryStore;$payments=new ShopFixturePayments;$mail=new ShopFixtureMail;$config=new ShopFixtureConfig;
$s=new ShopFixtureService(['shopstore'=>$store,'paymentservice'=>$payments,'user'=>new ShopFixtureUser,'communicationservice'=>$mail,'dbsysconfig'=>$config,'altconfig'=>$config,'language'=>new ShopFixtureLanguage]);
function check($v,$m){if(!$v)throw new RuntimeException($m);}
$s->saveSettings(['revision'=>1,'max_quantity'=>'100','terms'=>'Fixture terms','enabled'=>'1']);
$input=['id'=>str_repeat('b',32),'revision'=>0,'title'=>'Support','price'=>'115','status'=>'published','product_type'=>'virtual','virtual_kind'=>'contribution','send_thank_you'=>'1','thank_you_message'=>'Thank you for supporting our webinars.'];
$b=$s->saveBook($input);$cart=[$b['id']=>1];$q=$s->quote($cart);
check($q['lines'][0]['thank_you']===$input['thank_you_message'],'Message not frozen');
$o=$s->prepare($cart,['name'=>'Fixture','email'=>'buyer@example.invalid','accept_terms'=>'1','quote_hash'=>$s->quoteHash($q)],str_repeat('a',64));$token=$s->token($o['id']);$s->checkout($token);$o=$s->order($token);$intent=$payments->rows[$o['intent_id']];
$input['revision']=$b['revision'];$input['thank_you_message']='Later changed message';$s->saveBook($input);
check(!$s->fulfil($intent)['ok'] && !$mail->rows,'Unverified payment sent mail');
$intent['state']='failed';check(!$s->fulfil($intent)['ok'] && !$mail->rows,'Failed payment sent mail');
$intent['state']='succeeded';$payments->rows[$intent['id']]=$intent;$s->fulfil($intent);$s->fulfil($intent);
check(count($mail->rows)===1,'Duplicate notification');$message=array_values($mail->rows)[0]['text'];
check(str_contains($message,'Thank you for supporting our webinars.')&&!str_contains($message,'Later changed message'),'Message did not use order snapshot');
$input['id']=str_repeat('c',32);$input['revision']=0;$input['send_thank_you']='';$b=$s->saveBook($input);check(!isset($b['virtual']['thank_you']),'Unchecked message enabled');
$input['id']=str_repeat('d',32);$input['send_thank_you']='1';$input['thank_you_message']=' ';try{$s->saveBook($input);throw new RuntimeException('Blank message accepted');}catch(DomainException $e){check($e->getMessage()==='thank_you_required','Wrong validation');}
echo "PASS: opt-in acknowledgement, immutable order text, failed/unverified silence, duplicate confirmation protection, unchecked/blank message validation.\n";
