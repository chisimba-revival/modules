<?php
/** In-person event publishing, inventory, booking and verified ticket fulfilment.
 * @author Derek Keats <derek@dkeats.com>
 */
if(empty($GLOBALS['kewl_entry_point_run'])) die('No direct access');
class eventservice extends ChisimbaObject
{
    private $store; private $policy; private $catalog;
    public function init()
    {
        $this->store=$this->getObject('eventstore');
        $this->policy=$this->getObject('eventpolicy');
        $this->catalog=$this->getObject('paymentcatalogservice','payment-service');
    }
    public function text($key)
    { return html_entity_decode($this->getObject('language','language')->code2Txt('mod_events_'.$key,'events'),ENT_QUOTES,'UTF-8'); }
    public function now() { return time(); }
    public function details(array $row,$field='details')
    { return json_decode($row[$field]??'{}',true,512,JSON_THROW_ON_ERROR); }
    private function json($value) { return json_encode($value,JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE); }
    private function field(array $input,$key,$max,$required=false)
    {
        $value=$input[$key]??'';
        if(!is_string($value)||mb_strlen($value)>$max||preg_match('/[\x00-\x08\x0b\x0c\x0e-\x1f\x7f]/',$value)) throw new DomainException('field:'.$key.':field_length');
        $value=trim($value); if($required&&$value==='') throw new DomainException('field:'.$key.':field_required'); return $value;
    }
    private function integer($value,$min,$max)
    { $n=filter_var($value,FILTER_VALIDATE_INT,['options'=>['min_range'=>$min,'max_range'=>$max]]); if($n===false) throw new DomainException('invalid'); return $n; }
    private function moneyInput($value,$allowZero=false)
    {
        if(!is_string($value)||!preg_match('/^(0|[1-9][0-9]{0,6})(?:\.([0-9]{1,2}))?$/D',trim($value),$m)) throw new DomainException('invalid');
        $minor=(int)$m[1]*100+(int)str_pad($m[2]??'',2,'0');
        return $this->integer($minor,$allowZero?0:1,100000000);
    }
    /** Extract VAT from an inclusive price using integer arithmetic and cent rounding. */
    public static function inclusiveVat($amountMinor, $percentage)
    {
        if(!is_string($percentage)||!preg_match('/^(0|[1-9][0-9]{0,2})(?:\.([0-9]{1,2}))?$/D',trim($percentage),$m)) throw new DomainException('field:vat_percent:vat_percent_invalid');
        $rate=(int)$m[1]*100+(int)str_pad($m[2]??'',2,'0');
        if($rate>10000) throw new DomainException('field:vat_percent:vat_percent_invalid');
        $denominator=10000+$rate;
        return intdiv((int)$amountMinor*$rate+intdiv($denominator,2),$denominator);
    }
    public static function vatPercentage($amountMinor,$vatMinor)
    {
        $net=(int)$amountMinor-(int)$vatMinor;
        return $net>0?number_format((int)$vatMinor*100/$net,2,'.',''):'0.00';
    }
    public function bookingTerms(array $event)
    {
        $terms=$this->details($event)['terms']??'';
        return $terms!==''?$terms:(string)$this->getObject('dbsysconfig','sysconfig')->getValue('EVENTS_BOOKING_TERMS','events');
    }
    public function event($id) { return $this->store->one('events',$id); }
    public function occurrence($id) { return $this->store->one('occurrences',$id); }
    public function saveEvent(array $input)
    {
        return $this->store->transaction(function()use($input){
            $createId=$input['create_id']??bin2hex(random_bytes(16));
            if(!is_string($createId)||!preg_match('/^[a-f0-9]{32}$/D',$createId)) throw new DomainException('invalid');
            $old=$this->store->one('events',!empty($input['id'])?$input['id']:$createId,true);
            if((!empty($input['id'])&&!$old)||($old?!$this->policy->canManage($old):!$this->policy->canCreate())) throw new DomainException('forbidden');
            if($old&&empty($input['id'])) return $old;
            if($old&&(int)($input['revision']??0)!==(int)$old['revision']) throw new DomainException('stale');
            $status=$input['status']??'draft'; if(!in_array($status,['draft','published','archived'],true)) throw new DomainException('invalid');
            $details=[];
            foreach(['public_area'=>191,'host'=>191,'host_user_id'=>25,'host_reference'=>80,'accessibility'=>2000,'difficulty'=>500,'difficulty_notes'=>2000,'bring'=>2000,'included'=>2000,'weather'=>2000,'terms'=>4000,'contact'=>500,'followup'=>12000,'image_url'=>1000,'image_alt'=>500,'gallery'=>10000] as $key=>$max) $details[$key]=$this->field($input,$key,$max);
            if($details['host_reference']===''&&$details['host_user_id']!==''&&!$this->getObject('userservice','security')->findByUserId($details['host_user_id'])) throw new DomainException('field:host_user_id:host_not_found');
            if($details['host_reference']!==''&&!$this->getObject('hostservice','host-service')->profile($details['host_reference'])) throw new DomainException('field:host_reference:host_not_found');
            if($details['host_reference']!=='') { $details['host']=''; $details['host_user_id']=''; }
            if($details['image_url']!==''&&(!filter_var($details['image_url'],FILTER_VALIDATE_URL)||!in_array(parse_url($details['image_url'],PHP_URL_SCHEME),['https','http'],true))) throw new DomainException('field:image_url:image_invalid');
            $gallery=array_values(array_filter(array_map('trim',explode("\n",$details['gallery']))));
            if(count($gallery)>8) throw new DomainException('field:gallery:gallery_invalid');
            foreach($gallery as $image) if(!filter_var($image,FILTER_VALIDATE_URL)||strlen($image)>1000||!in_array(parse_url($image,PHP_URL_SCHEME),['https','http'],true)) throw new DomainException('field:gallery:gallery_invalid');
            $details['gallery']=$gallery;
            $row=['id'=>$old['id']??$createId,'owner_id'=>$old['owner_id']??(string)$this->getObject('user','security')->userId(),
                'title'=>$this->field($input,'title',191,true),'summary'=>$this->field($input,'summary',500,true),'description'=>$this->field($input,'description',20000,true),
                'status'=>$status,'revision'=>($old?(int)$old['revision']:0)+1,'details'=>$this->json($details)];
            if($old) $this->store->save('events',$row['id'],$row); else $this->store->add('events',$row);
            return $row;
        });
    }
    public function saveOccurrence(array $input)
    {
        return $this->store->transaction(function()use($input){
            $event=$this->event($input['event_id']??'');
            if(!$event||!$this->policy->canManage($event)) throw new DomainException('forbidden');
            $createId=$input['create_id']??bin2hex(random_bytes(16));
            if(!is_string($createId)||!preg_match('/^[a-f0-9]{32}$/D',$createId)) throw new DomainException('invalid');
            $old=$this->store->one('occurrences',!empty($input['id'])?$input['id']:$createId,true);
            if((!empty($input['id'])&&!$old)||($old&&$old['event_id']!==$event['id'])) throw new DomainException('forbidden');
            if($old&&empty($input['id'])) return $old;
            if($old&&(int)($input['revision']??0)!==(int)$old['revision']) throw new DomainException('stale');
            $timezone=$this->field($input,'timezone',64,true); $dates=$this->getObject('timeanddateservice','timeanddate-service'); $times=[];
            foreach(['starts_at','ends_at','closes_at'] as $key) {
                $date=$dates->parseLocal($input[$key]??'',$timezone,'Y-m-d\TH:i');
                if(!$date) throw new DomainException('field:'.$key.':date_invalid'); $times[$key]=$date->getTimestamp();
            }
            if($times['ends_at']<=$times['starts_at']) throw new DomainException('field:ends_at:end_invalid');
            if($times['closes_at']>$times['starts_at']) throw new DomainException('field:closes_at:deadline_invalid');
            $capacity=$this->integer($input['capacity']??null,1,100000);
            if($old&&$capacity<$this->store->occupancy($old['id'],$this->now())) throw new DomainException('capacity_too_small');
            $status=$input['status']??'open'; if(!in_array($status,['open','closed','cancelled'],true)) throw new DomainException('invalid');
            if($old&&$old['status']==='cancelled'&&$status!=='cancelled') throw new DomainException('cancelled');
            $amount=isset($input['price'])?$this->moneyInput($input['price']):$this->integer($input['amount_minor']??null,1,100000000);
            $rate=null;
            if(isset($input['vat_percent'])) {
                $rate=trim($input['vat_percent']); $vat=self::inclusiveVat($amount,$rate);
                if($old) {
                    $previous=$this->catalog->productVersion($old['product_code'],$old['price_version'])['price']??null;
                    $oldRate=$this->details($old)['vat_percent']??($previous?self::vatPercentage($previous['amount_minor'],$previous['vat_minor']??0):null);
                    // Editing unrelated fields must not alter an existing rounded VAT amount.
                    if($previous&&$amount===(int)$previous['amount_minor']&&$oldRate!==null&&(float)$oldRate===(float)$rate) $vat=(int)($previous['vat_minor']??0);
                }
            } else $vat=isset($input['vat'])?$this->moneyInput($input['vat'],true):$this->integer($input['vat_minor']??0,0,$amount);
            if($vat>$amount) throw new DomainException('field:vat:vat_invalid');
            $currency=strtoupper($this->field($input,'currency',3,true)); if(!preg_match('/^[A-Z]{3}$/D',$currency)) throw new DomainException('invalid');
            $id=$old['id']??$createId; $revision=($old?(int)$old['revision']:0)+1;
            $product=$this->catalog->createProduct(['code'=>'event-'.$id,'name'=>$event['title'],'purposeType'=>'event','purposeId'=>$id,'billingPeriod'=>'one_off']);
            if(empty($product['ok'])) throw new RuntimeException('Event product creation failed');
            $version='v'.$revision;
            $price=$this->catalog->addPrice($product['productId'],['versionCode'=>$version,'amountMinor'=>$amount,'vatMinor'=>$vat,'currency'=>$currency]);
            if(empty($price['ok'])) throw new RuntimeException('Event price creation failed');
            $helpers=array_values(array_filter(array_map('trim',explode(',',$this->field($input,'helpers',1000)))));
            foreach($helpers as $helper) if(!$this->getObject('userservice','security')->findByUserId($helper)) throw new DomainException('invalid');
            $row=['id'=>$id,'event_id'=>$event['id']]+$times+['timezone'=>$timezone,'capacity'=>$capacity,'status'=>$status,'revision'=>$revision,'product_code'=>'event-'.$id,'price_version'=>$version,
                'private_details'=>$this->json(['directions'=>$this->field($input,'directions',6000),'parking'=>$this->field($input,'parking',2000),'meeting'=>$this->field($input,'meeting',4000)]),
                'details'=>$this->json(['vat_percent'=>$rate,'helpers'=>$helpers,'change_message'=>$this->field($input,'change_message',2000)])];
            if($old) $this->store->save('occurrences',$id,$row); else $this->store->add('occurrences',$row);
            // Cancellation revokes admission immediately. A provider refund remains a separate, visible operation.
            if($status==='cancelled') foreach($this->store->rows('bookings',['occurrence_id'=>$id]) as $b) {
                if($b['state']==='confirmed') $this->store->save('bookings',$b['id'],['state'=>'refund_required']);
                foreach($this->store->rows('tickets',['booking_id'=>$b['id']]) as $t) $this->store->save('tickets',$t['id'],['state'=>'cancelled']);
            }
            return $row;
        });
    }
    /** Whitelist public fields. Never return an occurrence's private payload or helpers. */
    public function publicEvent($id)
    {
        $e=$this->event($id); if(!$e||$e['status']!=='published') return null;
        $d=$this->details($e); $d['terms']=$this->bookingTerms($e); unset($d['followup']);
        $d['host_biography']=!empty($d['host_user_id'])?$this->getObject('authorbiographyservice','userdetails')->forUser($d['host_user_id'])['biography']:'';
        if(!empty($d['host_reference'])&&($host=$this->getObject('hostservice','host-service')->profile($d['host_reference']))) { $d['host']=$host['name']; $d['host_biography']=$host['biography']; $d['host_image_url']=$host['image_url']; }
        if(in_array($d['difficulty'],['easy','moderate','strenuous'],true)) $d['difficulty']=$this->text($d['difficulty']);
        $d['difficulty_notes']=$d['difficulty_notes']??'';
        unset($d['host_user_id'],$d['host_reference']);
        $result=['id'=>$e['id'],'title'=>$e['title'],'summary'=>$e['summary'],'description'=>$e['description'],'details'=>$d,'occurrences'=>[],'reviews'=>[]];
        foreach($this->store->rows('occurrences',['event_id'=>$id]) as $o) {
            $p=$this->catalog->productVersion($o['product_code'],$o['price_version']);
            if($o['status']!=='cancelled'&&$this->now()>=(int)$o['ends_at']) $o['status']='completed';
            elseif($o['status']==='open'&&$this->now()>=(int)$o['closes_at']) $o['status']='closed';
            $result['occurrences'][]=array_intersect_key($o,array_flip(['id','starts_at','ends_at','closes_at','timezone','status']))+
                ['remaining'=>max(0,(int)$o['capacity']-$this->store->occupancy($o['id'],$this->now())),'price'=>$p['price']??null];
        }
        usort($result['occurrences'],fn($a,$b)=>$a['starts_at']<=>$b['starts_at']);
        foreach($this->store->rows('reviews',['event_id'=>$id,'status'=>'published','publish_consent'=>1]) as $r)
            $result['reviews'][]=array_intersect_key($r,array_flip(['rating','comment','display_name','reply']));
        return $result;
    }
    public function catalogue()
    { $rows=[]; foreach($this->store->rows('events',['status'=>'published']) as $e) $rows[]=$this->publicEvent($e['id']); return $rows; }
    /** Public cards use the nearest upcoming and most recent past date per event. */
    public function catalogueSections(array $events)
    {
        $sections=['upcoming'=>[],'past'=>[]];
        foreach($events as $event) {
            $future=[]; $past=[];
            foreach($event['occurrences'] as $date) {
                if((int)$date['ends_at']>$this->now()) $future[]=$date; else $past[]=$date;
            }
            usort($future,fn($a,$b)=>(int)$a['starts_at']<=>(int)$b['starts_at']);
            usort($past,fn($a,$b)=>(int)$b['starts_at']<=>(int)$a['starts_at']);
            $next=null; foreach($future as $candidate) if((int)$candidate['starts_at']>$this->now()) { $next=$candidate; break; }
            if($future||!$past) $sections['upcoming'][]=['event'=>$event,'date'=>$next??($future[0]??null)];
            if($past) $sections['past'][]=['event'=>$event,'date'=>$past[0]];
        }
        usort($sections['upcoming'],fn($a,$b)=>($a['date']['starts_at']??PHP_INT_MAX)<=>($b['date']['starts_at']??PHP_INT_MAX));
        usort($sections['past'],fn($a,$b)=>$b['date']['starts_at']<=>$a['date']['starts_at']);
        return $sections;
    }
    /** Discover assigned check-in work without disclosing purchaser or location data. */
    public function checkInAssignments()
    {
        $rows=[];
        foreach($this->store->rows('occurrences') as $date) {
            if((int)$date['ends_at']+14400<$this->now()||$date['status']==='cancelled') continue;
            $event=$this->event($date['event_id']);
            if($event&&$this->policy->canCheckIn($event,$date)) $rows[]=['id'=>$date['id'],'title'=>$event['title'],'starts_at'=>$date['starts_at'],'timezone'=>$date['timezone']];
        }
        usort($rows,fn($a,$b)=>$a['starts_at']<=>$b['starts_at']);return $rows;
    }
    public function managed()
    { return array_values(array_filter($this->store->rows('events'),fn($e)=>$this->policy->canManage($e))); }
    private function contact(array $input)
    {
        $name=$this->field($input,'name',191,true); $email=strtolower($this->field($input,'email',254,true));
        if(!filter_var($email,FILTER_VALIDATE_EMAIL)||preg_match('/[\r\n]/',$name)) throw new DomainException('invalid');
        return [$name,$email];
    }
    public function prepareBooking($occurrenceId,array $input,$requestKey,$offerToken='')
    {
        if(!preg_match('/^[a-f0-9]{64}$/D',(string)$requestKey)) throw new DomainException('invalid');
        [$name,$email]=$this->contact($input);
        $quantity=$this->integer($input['quantity']??null,1,10);
        if(empty($input['accept_terms'])) throw new DomainException('terms_required');
        $attendees=array_values(array_filter(array_map('trim',explode("\n",$this->field($input,'attendees',2000,true))),fn($n)=>$n!==''));
        if(count($attendees)!==$quantity) throw new DomainException('attendee_count');
        foreach($attendees as $n) if(mb_strlen($n)>191) throw new DomainException('attendee_count');
        $provider=(string)$this->getObject('dbsysconfig','sysconfig')->getValue('EVENTS_PAYMENT_PROVIDER','events');
        if(!in_array($provider,['yoco','paystack'],true)||!$this->getObject('paymentservice','payment-service')->providerAvailable($provider)) throw new DomainException('checkout_unavailable');
        return $this->store->transaction(function()use($occurrenceId,$name,$email,$quantity,$attendees,$requestKey,$offerToken,$input,$provider){
            $o=$this->store->one('occurrences',$occurrenceId,true); $e=$o?$this->event($o['event_id']):null;
            if(!$o||!$e||$e['status']!=='published'||$o['status']!=='open'||$this->now()>=(int)$o['closes_at']) throw new DomainException('closed');
            $existing=$this->store->rows('bookings',['request_hash'=>hash('sha256',$requestKey)])[0]??null;
            if($existing) { if($existing['occurrence_id']!==$o['id']) throw new DomainException('invalid'); return $existing; }
            $offerId=$offerToken!==''?$this->policy->tokenId('waitlist',$offerToken):null;
            if($offerId) {
                $w=$this->store->one('waitlist',$offerId);
                if(!$w||$w['occurrence_id']!==$o['id']||$w['state']!=='offered'||(int)$w['expires_at']<=$this->now()||$w['email']!==$email||$quantity!==1) throw new DomainException('offer_expired');
                $this->store->save('waitlist',$w['id'],['state'=>'booked']);
            } elseif($offerToken!=='') throw new DomainException('offer_expired');
            if($this->store->occupancy($o['id'],$this->now())+$quantity>(int)$o['capacity']) throw new DomainException('sold_out');
            $p=$this->catalog->purchasable($o['product_code'],$o['price_version']);
            if(!$p||$p['purpose_type']!=='event'||$p['purpose_id']!==$o['id']) throw new DomainException('checkout_unavailable');
            $b=['id'=>bin2hex(random_bytes(16)),'occurrence_id'=>$o['id'],'request_hash'=>hash('sha256',$requestKey),'name'=>$name,'email'=>$email,'quantity'=>$quantity,'state'=>'held','expires_at'=>min($this->now()+900,(int)$o['closes_at']),'created_at'=>$this->now(),
                'product_code'=>$p['code'],'price_version'=>$p['price']['version_code'],'amount_minor'=>(int)$p['price']['amount_minor']*$quantity,'vat_minor'=>(int)($p['price']['vat_minor']??0)*$quantity,'currency'=>$p['price']['currency'],'provider'=>$provider,
                'details'=>$this->json(['attendees'=>$attendees,'terms'=>$this->bookingTerms($e),'terms_accepted_at'=>$this->now(),'campaign'=>$this->field($input,'campaign',120)])];
            return $this->store->add('bookings',$b);
        });
    }
    public function booking($token)
    { $id=$this->policy->tokenId('booking',$token); return $id?$this->store->one('bookings',$id):null; }
    public function intentFor(array $booking)
    { return $this->getObject('dbpaymentintents','payment-service')->byIdempotency('event:'.$booking['id']); }
    public function matchesIntent(array $v)
    {
        $b=$this->store->one('bookings',$v['purpose_id']??'');
        if(!$b||($v['user_id']??null)!==null||($v['purpose_type']??'')!=='event'||$v['provider_code']!==$b['provider']||$v['idempotency_key']!=='event:'.$b['id']) return false;
        foreach(['amount_minor','currency','product_code','price_version'] as $key) if((string)$v[$key]!== (string)$b[$key]) return false;
        return true;
    }
    public function checkout(array $b)
    {
        $b=$this->store->one('bookings',$b['id']);
        if(!$b||$b['state']!=='held'||(int)$b['expires_at']<=$this->now()) throw new DomainException('hold_expired');
        $payments=$this->getObject('paymentservice','payment-service');
        $i=$payments->createIntent(['userId'=>null,'purposeType'=>'event','purposeId'=>$b['id'],'productCode'=>$b['product_code'],'priceVersion'=>$b['price_version'],'amountMinor'=>(int)$b['amount_minor'],'currency'=>$b['currency'],'provider'=>$b['provider'],'idempotencyKey'=>'event:'.$b['id'],'correlationId'=>'event:'.$b['id']]);
        if(empty($i['ok'])) throw new DomainException('checkout_unavailable');
        $url=$this->url('booking',['token'=>$this->policy->token('booking',$b['id'])]);
        return $payments->startCheckout($i['intentId'],['email'=>$b['email'],'successUrl'=>$url,'cancelUrl'=>$url,'failureUrl'=>$url]);
    }
    /** Called by payment-service only. Re-read the ledger to reject forged success arrays. */
    public function fulfil(array $intent)
    {
        $canonical=$this->getObject('paymentservice','payment-service')->intent($intent['id']??'');
        if(!$canonical||$canonical['state']!=='succeeded'||!$this->matchesIntent($canonical)) return ['ok'=>false,'code'=>'not_paid'];
        $b=$this->store->one('bookings',$canonical['purpose_id']);
        $result=$this->store->transaction(function()use($b){
            $o=$this->store->one('occurrences',$b['occurrence_id'],true); $b=$this->store->one('bookings',$b['id'],true);
            if($b['state']==='confirmed') return ['ok'=>true,'code'=>'already_issued'];
            if(in_array($b['state'],['refunded','reversed','disputed','refund_required'],true)) return ['ok'=>false,'code'=>'refund_required'];
            // An expired hold can be honoured only if capacity is still available.
            if($o['status']==='cancelled'||$this->now()>=(int)$o['starts_at']||$this->store->occupancy($o['id'],$this->now(),$b['id'])+(int)$b['quantity']>(int)$o['capacity']) {
                $this->store->save('bookings',$b['id'],['state'=>'refund_required']);
                return ['ok'=>false,'code'=>'refund_required'];
            }
            foreach($this->details($b)['attendees'] as $name) {
                $t=['id'=>bin2hex(random_bytes(16)),'booking_id'=>$b['id'],'occurrence_id'=>$o['id'],'attendee'=>$name,'state'=>'valid','revision'=>1,'checked_at'=>0,'checked_by'=>''];
                $t['code_hash']=hash('sha256',$this->policy->ticketCode($t)); $this->store->add('tickets',$t);
            }
            $this->store->save('bookings',$b['id'],['state'=>'confirmed']);
            return ['ok'=>true,'code'=>'tickets_issued'];
        });
        if(!empty($result['ok'])) {
            $queued=$this->notifyBooking($this->store->one('bookings',$b['id']),'confirmation');
            if(empty($queued['ok'])) return ['ok'=>false,'code'=>'ticket_email_pending'];
        }
        return $result;
    }
    public function reverse(array $intent)
    {
        $canonical=$this->getObject('paymentservice','payment-service')->intent($intent['id']??'');
        if(!$canonical||!in_array($canonical['state'],['refunded','reversed','disputed'],true)||!$this->matchesIntent($canonical)) return ['ok'=>false,'code'=>'not_reversed'];
        $b=$this->store->one('bookings',$canonical['purpose_id']);
        return $this->store->transaction(function()use($b,$canonical){
            $this->store->one('occurrences',$b['occurrence_id'],true);
            $this->store->save('bookings',$b['id'],['state'=>$canonical['state']]);
            foreach($this->store->rows('tickets',['booking_id'=>$b['id']]) as $t) $this->store->save('tickets',$t['id'],['state'=>'cancelled']);
            return ['ok'=>true,'code'=>'tickets_revoked'];
        });
    }
    public function bookingTickets(array $b)
    { return $this->store->rows('tickets',['booking_id'=>$b['id']]); }
    public function ticket($token)
    {
        if(!is_string($token)) return null; $id=explode('.',$token)[0]; $t=$this->store->one('tickets',$id);
        if(!$t||!$this->policy->tokenId('ticket',$token,$t['revision'])) return null;
        $b=$this->store->one('bookings',$t['booking_id']); $o=$this->occurrence($t['occurrence_id']);
        if(!$b||$b['state']!=='confirmed'||$t['state']!=='valid'||!$o||$o['status']==='cancelled') return null;
        return ['ticket'=>$t,'occurrence'=>$o,'event'=>$this->event($o['event_id'])];
    }
    public function checkIn($occurrenceId,$code)
    {
        return $this->store->transaction(function()use($occurrenceId,$code){
            $o=$this->store->one('occurrences',$occurrenceId,true); $e=$o?$this->event($o['event_id']):null;
            if(!$o||!$e||!$this->policy->canCheckIn($e,$o)) throw new DomainException('forbidden');
            $code=strtoupper(preg_replace('/[\s-]/','',(string)$code));
            if(!preg_match('/^[A-F0-9]{20}$/D',$code)) throw new DomainException('invalid_ticket');
            $t=$this->store->rows('tickets',['code_hash'=>hash('sha256',$code)])[0]??null;
            if(!$t) throw new DomainException('invalid_ticket');
            if($t['occurrence_id']!==$o['id']) throw new DomainException('wrong_event');
            $b=$this->store->one('bookings',$t['booking_id']);
            if($t['state']!=='valid'||$b['state']!=='confirmed'||$o['status']==='cancelled') throw new DomainException('cancelled');
            if((int)$t['checked_at']>0) throw new DomainException('already_checked');
            if($this->now()<(int)$o['starts_at']-14400||$this->now()>(int)$o['ends_at']+14400) throw new DomainException('checkin_window');
            $this->store->save('tickets',$t['id'],['checked_at'=>$this->now(),'checked_by'=>(string)$this->getObject('user','security')->userId()]);
            return $t['attendee'];
        });
    }
    public function transfer(array $booking,$ticketId,$name)
    {
        $name=$this->field(['name'=>$name],'name',191,true);
        return $this->store->transaction(function()use($booking,$ticketId,$name){
            $o=$this->store->one('occurrences',$booking['occurrence_id'],true); $b=$this->store->one('bookings',$booking['id']); $t=$this->store->one('tickets',$ticketId,true);
            if(!$t||$t['booking_id']!==$b['id']||$b['state']!=='confirmed'||$t['state']!=='valid'||$o['status']==='cancelled'||(int)$t['checked_at']>0||$this->now()>=(int)$o['starts_at']) throw new DomainException('transfer_closed');
            $t['attendee']=$name; $t['revision']=(int)$t['revision']+1; $t['code_hash']=hash('sha256',$this->policy->ticketCode($t)); $this->store->save('tickets',$t['id'],$t);
        });
    }
    public function review($token,array $input)
    {
        $data=$this->ticket($token);
        if(!$data||(int)$data['ticket']['checked_at']===0||$this->now()<(int)$data['occurrence']['ends_at']) throw new DomainException('review_closed');
        return $this->store->transaction(function()use($data,$input){
            $t=$this->store->one('tickets',$data['ticket']['id'],true);
            if($t['state']!=='valid'||$this->store->one('bookings',$t['booking_id'])['state']!=='confirmed') throw new DomainException('review_closed');
            $existing=$this->store->rows('reviews',['ticket_id'=>$t['id']])[0]??null;
            $row=['id'=>$existing['id']??bin2hex(random_bytes(16)),'ticket_id'=>$t['id'],'event_id'=>$data['event']['id'],'rating'=>$this->integer($input['rating']??null,1,5),'comment'=>$this->field($input,'comment',5000,true),'display_name'=>$this->field($input,'display_name',191),
                'publish_consent'=>empty($input['publish_consent'])?0:1,'status'=>'pending','reply'=>'','moderation_reason'=>'','created_at'=>$this->now()];
            if($existing) $this->store->save('reviews',$row['id'],$row); else $this->store->add('reviews',$row);
        });
    }
    public function moderate($id,$status,$reason,$reply)
    {
        $r=$this->store->one('reviews',$id); $e=$r?$this->event($r['event_id']):null;
        if(!$e||!$this->policy->canManage($e)||!in_array($status,['published','hidden'],true)||($status==='published'&&empty($r['publish_consent']))) throw new DomainException('forbidden');
        $reason=$this->field(['reason'=>$reason],'reason',500,$status==='hidden'); $reply=$this->field(['reply'=>$reply],'reply',3000);
        $this->store->save('reviews',$id,['status'=>$status,'moderation_reason'=>$reason,'reply'=>$reply]);
    }
    public function joinWaitlist($id,array $input)
    {
        [$name,$email]=$this->contact($input);
        return $this->store->transaction(function()use($id,$name,$email){
            $o=$this->store->one('occurrences',$id,true); $e=$o?$this->event($o['event_id']):null;
            if(!$e||$e['status']!=='published'||$o['status']!=='open'||$this->now()>=(int)$o['closes_at']) throw new DomainException('closed');
            // One verified waitlist entry per address and occurrence; never subscribe to marketing.
            if($this->store->rows('waitlist',['occurrence_id'=>$id,'email'=>$email])) return;
            $w=['id'=>bin2hex(random_bytes(16)),'occurrence_id'=>$id,'name'=>$name,'email'=>$email,'state'=>'pending','created_at'=>$this->now(),'expires_at'=>$this->now()+86400];
            $this->store->add('waitlist',$w);
            $queued=$this->mail($email,'waitlist_verify',$e['title']."\n".$this->url('waitlistconfirm',['token'=>$this->policy->token('waitlist',$w['id'])]),'waitlist-verify:'.$w['id']);
            if(empty($queued['ok'])) throw new RuntimeException('Waitlist confirmation could not be queued');
        });
    }
    public function confirmWaitlist($token)
    {
        $id=$this->policy->tokenId('waitlist',$token); $w=$id?$this->store->one('waitlist',$id):null;
        if(!$w||$w['state']!=='pending'||(int)$w['expires_at']<=$this->now()) throw new DomainException('offer_expired');
        $this->store->save('waitlist',$id,['state'=>'waiting']);
    }
    public function url($action,array $params=[])
    { return rtrim($this->getObject('altconfig','config')->getSiteRoot(),'/').'/index.php?'.http_build_query(['module'=>'events','action'=>$action]+$params); }
    private function mail($email,$kind,$text,$key)
    { return $this->getObject('communicationservice','communications')->queueEmail(['to'=>$email,'subject'=>$this->text('mail_'.$kind),'text'=>$text,'idempotencyKey'=>'events:'.$key,'metadata'=>['purpose'=>'event_'.$kind]]); }
    public function notifyBooking(array $b,$kind)
    {
        $o=$this->occurrence($b['occurrence_id']); $e=$this->event($o['event_id']);
        $text=$this->text('mail_'.$kind)."\n\n".$e['title']."\n".$this->date($o['starts_at'],$o['timezone'])."\n".$this->url('booking',['token'=>$this->policy->token('booking',$b['id'])]);
        // Directions stay behind ticket authorisation, including in email and calendar attachments.
        return $this->mail($b['email'],$kind,$text,$kind.':'.$b['id'].':'.($kind==='confirmation'?1:$o['revision']));
    }
    public function date($epoch,$zone)
    { return $this->getObject('timeanddateservice','timeanddate-service')->formatDateTime(new DateTimeImmutable('@'.(int)$epoch),$zone); }
    /** Idempotent maintenance, suitable for an authenticated organiser action or CLI worker. */
    public function maintain($occurrenceId)
    {
        $o=$this->occurrence($occurrenceId); if(!$o) return;
        foreach($this->store->rows('bookings',['occurrence_id'=>$o['id']]) as $b) {
            $i=$this->intentFor($b);
            if($i&&$i['state']==='succeeded'&&$b['state']==='held') $this->fulfil($i);
            if($i&&in_array($i['state'],['refunded','reversed','disputed'],true)) $this->reverse($i);
            $b=$this->store->one('bookings',$b['id']);
            if(in_array($b['state'],['confirmed','refund_required'],true)) {
                $this->notifyBooking($b,$o['status']==='cancelled'?'cancelled':($b['state']==='refund_required'?'refund_required':'confirmation'));
                if($o['status']!=='cancelled'&&$b['state']==='confirmed') {
                    if($o['revision']>1) $this->notifyBooking($b,'changed');
                    if($this->now()>=(int)$o['starts_at']-86400&&$this->now()<(int)$o['starts_at']) $this->notifyBooking($b,'reminder');
                    if($this->now()>=(int)$o['ends_at']) $this->notifyBooking($b,'followup');
                }
            }
        }
        $this->store->transaction(function()use($o){
            $o=$this->store->one('occurrences',$o['id'],true);
            if($o['status']!=='open'||$this->now()>=(int)$o['closes_at']) return;
            $rows=$this->store->rows('waitlist',['occurrence_id'=>$o['id']]); usort($rows,fn($a,$b)=>$a['created_at']<=>$b['created_at']);
            foreach($rows as $w) {
                if($w['state']==='offered'&&(int)$w['expires_at']<=$this->now()) { $this->store->save('waitlist',$w['id'],['state'=>'expired']); continue; }
                if($w['state']!=='waiting'||$this->store->occupancy($o['id'],$this->now())>=(int)$o['capacity']) continue;
                $this->store->save('waitlist',$w['id'],['state'=>'offered','expires_at'=>min($this->now()+3600,(int)$o['closes_at'])]);
                $result=$this->mail($w['email'],'waitlist_offer',$this->url('view',['id'=>$o['event_id'],'offer'=>$this->policy->token('waitlist',$w['id'])]),'waitlist-offer:'.$w['id']);
                if(empty($result['ok'])) throw new RuntimeException('Waitlist offer could not be queued');
            }
        });
    }
}
