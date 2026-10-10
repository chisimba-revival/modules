<?php
/** Add retained synthetic attendance, full-date and review scenarios to a browser fixture.
 * @author Derek Keats <derek@dkeats.com>
 */
$eventId=$argv[1]??''; $argv[1]='--library'; require __DIR__.'/runtime_test.php';
$e=$s->event($eventId);
if(!$e||$e['owner_id']!=='events-test-user'||!str_starts_with($e['title'],'Disposable Events')) throw new RuntimeException('Supply the synthetic browser fixture ID');
if(count($store->rows('occurrences',['event_id'=>$eventId]))!==1) throw new RuntimeException('Scenarios already added; do not duplicate');
$links=[];
foreach(['arrival'=>time()+1800,'full'=>time()+7*86400,'feedback'=>time()-86400] as $scenario=>$at) {
    $s->clock=$at-86400;
    $o=$s->saveOccurrence(['event_id'=>$e['id'],'starts_at'=>gmdate('Y-m-d\TH:i',$at),'ends_at'=>gmdate('Y-m-d\TH:i',$at+3600),'closes_at'=>gmdate('Y-m-d\TH:i',$at-900),'timezone'=>'UTC','capacity'=>$scenario==='full'?'1':'8','price'=>'115.00','vat'=>'15.00','currency'=>'ZAR','status'=>'open','directions'=>'SYNTHETIC PRIVATE: example path only.','parking'=>'SYNTHETIC PRIVATE: example parking only.','meeting'=>'SYNTHETIC PRIVATE: example meeting place only.']);
    $b=$s->prepareBooking($o['id'],array_merge($input,['attendees'=>'Sample '.ucfirst($scenario).' Attendee']),bin2hex(random_bytes(32)));
    $i=['id'=>bin2hex(random_bytes(16)),'purpose_type'=>'event','purpose_id'=>$b['id'],'user_id'=>null,'provider_code'=>'yoco','idempotency_key'=>'event:'.$b['id'],'state'=>'succeeded'];
    foreach(['product_code','price_version','amount_minor','currency'] as $key)$i[$key]=$b[$key];
    $payments->rows[$i['id']]=$i; $s->fulfil($i); $ticket=$s->bookingTickets($b)[0];
    $token=$policy->token('ticket',$ticket['id'],$ticket['revision']);
    if($scenario==='feedback') {
        $s->clock=$at; $s->checkIn($o['id'],$policy->ticketCode($ticket)); $s->clock=$at+3601;
        $s->review($token,['rating'=>'4','comment'=>'Synthetic review: an enjoyable walk; more time for grasses would help.','publish_consent'=>'1','display_name'=>'Sample attendee']);
        $review=$store->rows('reviews',['ticket_id'=>$ticket['id']])[0]; $s->moderate($review['id'],'published','','Synthetic organiser reply: thank you for the suggestion.');
    }
    if($scenario==='full') { $s->joinWaitlist($o['id'],$input); $w=$store->rows('waitlist',['occurrence_id'=>$o['id']])[0]; $s->confirmWaitlist($policy->token('waitlist',$w['id'])); }
    $links[$scenario]=['operations'=>$s->url('operations',['id'=>$o['id']]),'ticket'=>$s->url('ticket',['token'=>$token]),'scan_code'=>$policy->ticketCode($ticket)];
}
echo json_encode($links,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES)."\n";
