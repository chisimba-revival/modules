<?php
/** Disposable browser fixture. All contact and location data is synthetic.
 * @author Derek Keats <derek@dkeats.com>
 */
$mode=$argv[1]??''; $fixtureId=$argv[2]??''; $argv[1]='--library'; require __DIR__.'/runtime_test.php';
if($mode==='--remove') {
    $e=$s->event($fixtureId);
    if(!$e||$e['owner_id']!=='events-test-user'||!str_starts_with($e['title'],'Disposable Events')) throw new RuntimeException('Not a browser fixture');
    foreach($store->rows('occurrences',['event_id'=>$e['id']]) as $o) {
        foreach(['tickets','bookings','waitlist'] as $table)$db->exec('DELETE FROM tbl_events_'.$table.' WHERE occurrence_id='.$db->quote($o['id']));
        $products=$db->queryAll('SELECT id FROM tbl_payment_service_products WHERE code='.$db->quote($o['product_code']),null,MDB2_FETCHMODE_ASSOC);
        foreach($products as $p)$db->exec('DELETE FROM tbl_payment_service_prices WHERE product_id='.$db->quote($p['id']));
        $db->exec('DELETE FROM tbl_payment_service_products WHERE code='.$db->quote($o['product_code']));
    }
    $db->exec('DELETE FROM tbl_events_reviews WHERE event_id='.$db->quote($e['id']));$db->exec('DELETE FROM tbl_events_occurrences WHERE event_id='.$db->quote($e['id']));$db->exec('DELETE FROM tbl_events_events WHERE id='.$db->quote($e['id']));echo "Fixture removed\n";exit;
}
if($mode!=='--create') {fwrite(STDERR,"Use --create or --remove ID\n");exit(64);}
$e=$s->saveEvent(['title'=>'Disposable Events browser preview','summary'=>'A synthetic nature walk for local interface testing.','description'=>'Explore grasses, trees and birds with a guide. This is a disposable test event, not a real advertised activity.','host'=>'Fixture Nature Guide','public_area'=>'Example region','status'=>'published','accessibility'=>'Uneven paths; contact the organiser to discuss access.','bring'=>'Water, walking shoes and binoculars.','terms'=>'Synthetic test terms. No real event or payment.','weather'=>'The guide will confirm arrangements before the walk.','followup'=>'Synthetic species list for attendee resources.']);
$at=time()+86400;$o=$s->saveOccurrence(['event_id'=>$e['id'],'starts_at'=>gmdate('Y-m-d\TH:i',$at),'ends_at'=>gmdate('Y-m-d\TH:i',$at+7200),'closes_at'=>gmdate('Y-m-d\TH:i',$at-3600),'timezone'=>'UTC','capacity'=>'12','amount_minor'=>'11500','vat_minor'=>'1500','currency'=>'ZAR','directions'=>'SYNTHETIC PRIVATE DIRECTIONS: follow the marked path.','parking'=>'SYNTHETIC PRIVATE PARKING: use the example car park.','meeting'=>'SYNTHETIC PRIVATE MEETING: gather by the example gate.','status'=>'open']);
$b=$s->prepareBooking($o['id'],$input,bin2hex(random_bytes(32)));
$i=['id'=>bin2hex(random_bytes(16)),'purpose_type'=>'event','purpose_id'=>$b['id'],'user_id'=>null,'provider_code'=>'yoco','idempotency_key'=>'event:'.$b['id'],'state'=>'succeeded'];foreach(['product_code','price_version','amount_minor','currency'] as $k)$i[$k]=$b[$k];$payments->rows[$i['id']]=$i;$s->fulfil($i);$t=$s->bookingTickets($b)[0];
echo json_encode(['event_id'=>$e['id'],'occurrence_id'=>$o['id'],'public_url'=>$s->url('view',['id'=>$e['id']]),'ticket_url'=>$s->url('ticket',['token'=>$policy->token('ticket',$t['id'],$t['revision'])]),'booking_url'=>$s->url('booking',['token'=>$policy->token('booking',$b['id'])])],JSON_UNESCAPED_SLASHES)."\n";
