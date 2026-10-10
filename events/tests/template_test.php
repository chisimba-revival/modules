<?php
/** Render organiser and attendee templates against a disposable fixture.
 * @author Derek Keats <derek@dkeats.com>
 */
$eventId=$argv[1]??'';$argv[1]='--library';require __DIR__.'/runtime_test.php';
/** Isolated view binding uses the real framework services and skin HTML.
 * @author Derek Keats <derek@dkeats.com>
 */
class EventTemplateFixture extends ChisimbaObject
{
    public function render($file,array $variables)
    { extract($variables,EXTR_SKIP);ob_start();try{require dirname(__DIR__).'/templates/content/'.$file;return ob_get_clean();}catch(Throwable $e){ob_end_clean();throw $e;} }
}
$e=$s->event($eventId);if(!$e||$e['owner_id']!=='events-test-user')throw new RuntimeException('Supply the disposable browser fixture ID');
$b=null; foreach($store->rows('occurrences',['event_id'=>$e['id']]) as $candidate) { $bookings=$store->rows('bookings',['occurrence_id'=>$candidate['id']]); if($bookings) { $o=$candidate; $b=$bookings[0]; break; } }
if(!$b)throw new RuntimeException('Supply a fixture with a simulated booking');
$ticket=$s->bookingTickets($b)[0];
$view=new EventTemplateFixture($engine,'events');
$vars=['eventAssignments'=>$s->checkInAssignments(),'eventService'=>$s,'eventPolicy'=>$policy,'eventCsrf'=>'synthetic-test-csrf','eventError'=>'','eventNotice'=>'','eventRecord'=>$e,'eventRecords'=>[$e],'eventOccurrence'=>$o,'eventPublic'=>$s->publicEvent($e['id']),'eventPublicRecords'=>[$s->publicEvent($e['id'])],'eventRequestKey'=>str_repeat('b',64),'eventOffer'=>'','eventCampaign'=>'','eventBooking'=>$b,'eventBookingTickets'=>[$ticket],'eventBookings'=>[$b],'eventTickets'=>[$ticket],'eventReviews'=>[],'eventToken'=>$policy->token('ticket',$ticket['id'],$ticket['revision']),'eventTicketData'=>['event'=>$e,'occurrence'=>$o,'ticket'=>$ticket]];
set_error_handler(function($level,$message,$file,$line){if(str_contains($file,'/events/'))throw new ErrorException($message,0,$level,$file,$line);return false;});
foreach(['catalogue','view','manage','editor','schedule','booking','ticket','operations','scan','error','message','waitlist_confirm','checkins'] as $name){$html=$view->render($name.'_tpl.php',$vars);if($html==='')throw new RuntimeException('Empty template '.$name);if(in_array($name,['catalogue','view'],true)&&str_contains($html,'SYNTHETIC PRIVATE'))throw new RuntimeException('Public template leaked location');}
$wideBlocks=$engine->getObject('dbmoduleblocks','modulecatalogue')->getBlocks('wide','site');
if(!array_filter($wideBlocks,static fn($row)=>$row['moduleid']==='events'&&$row['blockname']==='events'))throw new RuntimeException('Events missing from wide block selector');
$block=$engine->getObject('block_events','events');
$blockHtml=$block->show();
if(!str_contains($blockHtml,'chisimba-publication-card-grid')||str_contains($blockHtml,'SYNTHETIC PRIVATE'))throw new RuntimeException('Event block rendering or privacy failed');
restore_error_handler();echo "PASS: public Events block and all 13 organiser/attendee templates render without event warnings; public templates exclude private location details.\n";
