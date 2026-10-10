<?php
/** Local integration contracts; explicit opt-in, synthetic data, no provider or mail calls.
 * @author Derek Keats <derek@dkeats.com>
 */
if(PHP_SAPI!=='cli'||getenv('EVENTS_TEST_SITE')==='') { fwrite(STDERR,"Set EVENTS_TEST_SITE to an installed disposable development site root.\n"); exit(64); }
$root=getenv('EVENTS_TEST_SITE'); chdir($root);
$_SERVER['REQUEST_METHOD']='CLI'; $_SERVER['HTTP_HOST']='localhost'; $_SERVER['SCRIPT_NAME']='/index.php'; $_SERVER['QUERY_STRING']='';
$GLOBALS['kewl_entry_point_run']=true; require 'classes/core/engine_class_inc.php'; $engine=new engine();
$engine->loadClass('eventservice','events'); $engine->loadClass('eventpolicy','events');
/** Synthetic identity; never grants privileges to a persisted account. @author Derek Keats <derek@dkeats.com> */
class EventFixtureUser { public $admin=true; public $logged=true; public function isAdmin(){return $this->admin;} public function isLoggedIn(){return $this->logged;} public function userId(){return 'events-test-user';} }
/** In-memory outbox prevents external deliveries. @author Derek Keats <derek@dkeats.com> */
class EventFixtureMail { public $messages=[]; public function queueEmail($m){$this->messages[$m['idempotencyKey']]=$m;return ['ok'=>true];} }
/** Provider ledger fixture: no credentials or network. @author Derek Keats <derek@dkeats.com> */
class EventFixturePayments { public $rows=[]; public function providerAvailable($p){return $p==='yoco';} public function intent($id){return $this->rows[$id]??null;} }
/** Permission fixture uses the real capability signing implementation. @author Derek Keats <derek@dkeats.com> */
class EventFixturePolicy extends eventpolicy {
    public $user;
    public function __construct($engine,$user){$this->objEngine=$engine;$this->moduleName='events';$this->user=$user;}
    public function getObject($name,$moduleName=''){return $name==='user'?$this->user:parent::getObject($name,$moduleName);}
}
/** Domain service with a controllable clock and external boundaries replaced. @author Derek Keats <derek@dkeats.com> */
class EventFixtureService extends eventservice {
    public $overrides; public $clock;
    public function __construct($engine,$overrides){$this->objEngine=$engine;$this->moduleName='events';$this->overrides=$overrides;$this->clock=time();$this->init();}
    public function getObject($name,$moduleName=''){return $this->overrides[$name]??parent::getObject($name,$moduleName);}
    public function now(){return $this->clock;}
}
$user=new EventFixtureUser(); $policy=new EventFixturePolicy($engine,$user); $mail=new EventFixtureMail(); $payments=new EventFixturePayments();
$s=new EventFixtureService($engine,['eventpolicy'=>$policy,'user'=>$user,'communicationservice'=>$mail,'paymentservice'=>$payments]);
$store=$engine->getObject('eventstore','events'); $db=$engine->getDbObj();
$assert=function($condition,$message){if(!$condition)throw new RuntimeException($message);};
$reject=function($call,$expected)use($assert){try{$call();throw new RuntimeException('Expected rejection: '.$expected);}catch(DomainException $e){$assert($e->getMessage()===$expected,'Wrong rejection: '.$e->getMessage());}};
$input=['name'=>'Fixture Purchaser','email'=>'events-test@example.invalid','quantity'=>'1','attendees'=>'Fixture Attendee','accept_terms'=>'1','campaign'=>'fixture'];
if(($argv[1]??'')==='--race') {
    try {$s->prepareBooking($argv[2],$input,bin2hex(random_bytes(32))); echo "BOOKED\n";} catch(DomainException $e){echo $e->getMessage()."\n";} exit;
}
if(($argv[1]??'')==='--library') return;
$created=[];
try {
    $event=$s->saveEvent(['title'=>'Disposable Events QA','summary'=>'Synthetic test only','description'=>'Synthetic description','status'=>'published','public_area'=>'Broad public area','terms'=>'Synthetic terms','followup'=>'Private follow-up material']); $created[]=$event['id'];
    $reject(fn()=>$s->saveEvent(['id'=>$event['id'],'revision'=>0]),'stale');
    $user->logged=false; $assert(!$policy->canManage($event),'Anonymous cannot manage'); $reject(fn()=>$s->saveEvent(['title'=>'x']),'forbidden'); $user->logged=true;
    $makeDate=function($capacity)use($s,$event){$at=time()+86400;return $s->saveOccurrence(['event_id'=>$event['id'],'starts_at'=>gmdate('Y-m-d\TH:i',$at),'ends_at'=>gmdate('Y-m-d\TH:i',$at+7200),'closes_at'=>gmdate('Y-m-d\TH:i',$at-3600),'timezone'=>'UTC','capacity'=>(string)$capacity,'price'=>'115.00','vat_percent'=>'15','currency'=>'ZAR','directions'=>'SECRET DIRECTIONS','parking'=>'SECRET PARKING','meeting'=>'SECRET MEETING','status'=>'open']);};
    $o=$makeDate(2); $key=bin2hex(random_bytes(32)); $b=$s->prepareBooking($o['id'],$input,$key);
    $assert($s->prepareBooking($o['id'],$input,$key)['id']===$b['id'],'Idempotent reservation');
    $b2=$s->prepareBooking($o['id'],$input,bin2hex(random_bytes(32)));
    $reject(fn()=>$s->prepareBooking($o['id'],$input,bin2hex(random_bytes(32))),'sold_out');
    $public=json_encode($s->publicEvent($event['id']));
    foreach(['SECRET','private_details','helpers','follow-up'] as $secret)$assert(!str_contains($public,$secret),'Public payload leaked '.$secret);
    $assert($b['amount_minor']===11500&&$b['vat_minor']===1500,'Immutable gross and VAT totals');
    $assert(!$s->booking($policy->token('booking',str_repeat('a',32))),'Unknown booking denied');
    $assert(!$s->booking($policy->token('ticket',$b['id'])),'Wrong token scope denied');
    $ledger=function($b,$state='succeeded')use($payments){$i=['id'=>bin2hex(random_bytes(16)),'purpose_type'=>'event','purpose_id'=>$b['id'],'user_id'=>null,'provider_code'=>'yoco','idempotency_key'=>'event:'.$b['id'],'state'=>$state];foreach(['product_code','price_version','amount_minor','currency'] as $k)$i[$k]=$b[$k];$payments->rows[$i['id']]=$i;return $i;};
    $i=$ledger($b,'awaiting_approval'); $assert(!$s->fulfil(array_merge($i,['state'=>'succeeded']))['ok'],'Forged browser success cannot issue ticket');
    $payments->rows[$i['id']]['state']='succeeded'; $i=$payments->rows[$i['id']];
    $assert($s->fulfil($i)['ok'],'Verified payment issues ticket'); $s->fulfil($i);
    $tickets=$s->bookingTickets($b);$assert(count($tickets)===1,'Duplicate callback issues exactly one ticket: '.count($tickets).' state '.($store->one('bookings',$b['id'])['state']??'missing'));$assert(count($mail->messages)===1,'Confirmation email is idempotent');
    $t=$tickets[0]; $token=$policy->token('ticket',$t['id'],$t['revision']);$assert($s->ticket($token)!==null,'Valid ticket sees private arrival details');
    $s->transfer($b,$t['id'],'New Attendee'); $assert($s->ticket($token)===null,'Transfer invalidates old link');
    $oldcode=$policy->ticketCode($t); $t=$s->bookingTickets($b)[0];$token=$policy->token('ticket',$t['id'],$t['revision']);
    $s->clock=(int)$o['starts_at'];$reject(fn()=>$s->checkIn($o['id'],$oldcode),'invalid_ticket');
    $assert($s->checkIn($o['id'],$policy->ticketCode($t))==='New Attendee','Valid check-in');
    $reject(fn()=>$s->checkIn($o['id'],$policy->ticketCode($t)),'already_checked');
    $reject(fn()=>$s->transfer($b,$t['id'],'Other'),'transfer_closed');
    $s->clock=(int)$o['ends_at']+1; $s->review($token,['rating'=>'5','comment'=>'Private feedback']);
    $r=$store->rows('reviews',['ticket_id'=>$t['id']])[0];$reject(fn()=>$s->moderate($r['id'],'published','',''),'forbidden');
    $s->review($token,['rating'=>'3','comment'=>'Useful but too long','publish_consent'=>'1','display_name'=>'Tester']);$s->moderate($r['id'],'published','','Thank you');
    $assert(count($s->publicEvent($event['id'])['reviews'])===1,'Consented review publishes');
    $payments->rows[$i['id']]['state']='refunded';$assert($s->reverse($payments->rows[$i['id']])['ok'],'Refund revokes tickets');$assert(!$s->ticket($token),'Refund denies private ticket');
    $assert(!$s->fulfil($i)['ok'],'Stale success cannot revive refund');
    $s->clock=time();$o2=$makeDate(1);$late=$s->prepareBooking($o2['id'],$input,bin2hex(random_bytes(32)));$s->clock+=901;
    $replacement=$s->prepareBooking($o2['id'],$input,bin2hex(random_bytes(32)));$lateIntent=$ledger($late);$assert(!$s->fulfil($lateIntent)['ok'],'Late payment cannot oversell');
    $assert($store->one('bookings',$late['id'])['state']==='refund_required','Late payment flagged for refund');
    $assert(count($s->bookingTickets($late))===0,'No admission for oversold late payment');
    $o3=$makeDate(1);$s->clock=time();
    $command=[PHP_BINARY,__FILE__,'--race',$o3['id']];$processes=[];
    for($n=0;$n<2;$n++){ $pipes=[];$proc=proc_open($command,[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes);fclose($pipes[0]);$processes[]=[$proc,$pipes]; }
    $results=[];foreach($processes as [$proc,$pipes]){$results[]=trim(stream_get_contents($pipes[1]));$err=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);$exit=proc_close($proc);$assert($exit===0&&$err==='','Concurrent worker error: '.$err);}
    sort($results);$assert($results===['BOOKED','sold_out'],'Concurrent last-seat buyers cannot oversell: '.json_encode($results));
    $s->clock=time(); $multiDate=$makeDate(3);
    $multiInput=array_merge($input,['quantity'=>'2','attendees'=>"First Attendee\nSecond Attendee"]);
    $multi=$s->prepareBooking($multiDate['id'],$multiInput,bin2hex(random_bytes(32)));
    $assert($multi['amount_minor']===23000&&$multi['vat_minor']===3000,'Group purchase totals');
    $s->fulfil($ledger($multi)); $groupTickets=$s->bookingTickets($multi);
    $assert(count($groupTickets)===2&&$groupTickets[0]['code_hash']!==$groupTickets[1]['code_hash'],'One distinct ticket per attendee');
    $user->admin=false; $assert(!$policy->canManage($event),'Unprivileged owner has no organiser rights');
    $helperOccurrence=$multiDate; $helperOccurrence['details']=json_encode(['helpers'=>['events-test-user']]);
    $assert($policy->canCheckIn($event,$helperOccurrence),'Assigned helper can check in');
    $assert(!$policy->canCheckIn($event,$multiDate),'Unassigned helper denied');
    $assert($s->checkInAssignments()===[],'Unassigned user has no discoverable check-in duties');
    $store->save('occurrences',$multiDate['id'],['details'=>$helperOccurrence['details']]);
    $duties=$s->checkInAssignments(); $assert(count($duties)===1&&$duties[0]['id']===$multiDate['id'],'Assigned helper can discover their event');
    $assert(!str_contains(json_encode($duties),'SECRET')&&!isset($duties[0]['private_details']),'Assignment list excludes private location');
    $store->save('occurrences',$multiDate['id'],['details'=>$multiDate['details']]);
    $assert($s->checkInAssignments()===[],'Removed helper loses discoverable access immediately'); $user->admin=true;
    $waitDate=$makeDate(1); $s->joinWaitlist($waitDate['id'],$input);
    $wait=$store->rows('waitlist',['occurrence_id'=>$waitDate['id']])[0];
    $assert($wait['state']==='pending','Waitlist needs email confirmation');
    $offerToken=$policy->token('waitlist',$wait['id']); $s->confirmWaitlist($offerToken); $s->maintain($waitDate['id']);
    $assert($store->one('waitlist',$wait['id'])['state']==='offered','Released place offered');
    $reject(fn()=>$s->prepareBooking($waitDate['id'],$input,bin2hex(random_bytes(32))),'sold_out');
    $offered=$s->prepareBooking($waitDate['id'],$input,bin2hex(random_bytes(32)),$offerToken);
    $assert($offered['state']==='held'&&$store->one('waitlist',$wait['id'])['state']==='booked','Offer exchanges one reserved place atomically');
    $assert($store->occupancy($waitDate['id'],$s->now())===1,'Offer does not double-count inventory');
    echo "PASS: real database transactions, concurrent last seat, duplicate booking/callback, immutable VAT, private location filtering, scoped tokens, permission denial, transfers, check-in replay, review consent, refunds, late-payment recovery, group tickets, helper scopes and waitlist offers.\n";
} finally {
    // Only remove rows reachable from this run's synthetic event IDs.
    foreach($created as $id) {
        $occurrences=$store->rows('occurrences',['event_id'=>$id]);
        foreach($occurrences as $o) {
            foreach(['tickets','bookings','waitlist'] as $table)$db->exec('DELETE FROM tbl_events_'.$table.' WHERE occurrence_id='.$db->quote($o['id']));
            $products=$db->queryAll('SELECT id FROM tbl_payment_service_products WHERE code='.$db->quote($o['product_code']),null,MDB2_FETCHMODE_ASSOC);
            foreach($products as $p)$db->exec('DELETE FROM tbl_payment_service_prices WHERE product_id='.$db->quote($p['id']));
            $db->exec('DELETE FROM tbl_payment_service_products WHERE code='.$db->quote($o['product_code']));
        }
        $db->exec('DELETE FROM tbl_events_reviews WHERE event_id='.$db->quote($id));$db->exec('DELETE FROM tbl_events_occurrences WHERE event_id='.$db->quote($id));$db->exec('DELETE FROM tbl_events_events WHERE id='.$db->quote($id));
    }
}
