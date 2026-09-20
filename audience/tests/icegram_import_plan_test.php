<?php
$GLOBALS['kewl_entry_point_run']=true;require dirname(__DIR__).'/classes/icegramimportplan_class_inc.php';
function check($value,$message){if(!$value)throw new RuntimeException($message);}
$base=['id'=>'1','email'=>'SUBSCRIBER@example.org','first_name'=>'Test','last_name'=>'Person','created_at'=>'2020-01-01','status'=>'verified','unsubscribed'=>'0','bounce_status'=>'0','is_deliverable'=>'1'];
$membership=['contact_id'=>'1','status'=>'subscribed','subscribed_at'=>'2020-01-01','optin_type'=>'double'];
$p=icegramimportplan::build([$base],[$membership],[]);check($p['eligible'][0]['email']==='subscriber@example.org','Normalise address');
foreach([['unsubscribed'=>'1'],['status'=>'spam'],['is_deliverable'=>'0'],['bounce_status'=>'2']] as $override)check(!icegramimportplan::build([array_replace($base,$override)],[$membership],[])['eligible'],'Exclude suppression');
check(!icegramimportplan::build([$base],[],[])['eligible'],'Not a Users subscriber');check(!icegramimportplan::build([$base],[array_replace($membership,['status'=>'unsubscribed'])],[])['eligible'],'Respect list unsubscribe');
check(!icegramimportplan::build([$base],[$membership],['subscriber@example.org'])['eligible'],'Respect blocked address');
check(icegramimportplan::build([array_replace($base,['bounce_status'=>'1'])],[$membership],[])['summary']['eligible_soft_bounces']===1,'Soft bounce does not invent an unsubscribe');
try{icegramimportplan::build([$base,$base],[$membership],[]);throw new RuntimeException('Expected duplicate rejection');}catch(DomainException $e){}
try{icegramimportplan::build([$base],[$membership,$membership],[]);throw new RuntimeException('Expected membership rejection');}catch(DomainException $e){}
echo "PASS Users-list consent, global/list unsubscribe, spam, hard/soft bounce, blocked address and duplicate checks\n";
