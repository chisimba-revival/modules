<?php
/** Public event discovery, private bookings and authorised organiser actions.
 * @author Derek Keats <derek@dkeats.com>
 */
if(empty($GLOBALS['kewl_entry_point_run'])) die('No direct access');
class events extends controller
{
    private $service; private $store; private $policy; private $csrf;
    public function init()
    {
        $this->service=$this->getObject('eventservice'); $this->store=$this->getObject('eventstore'); $this->policy=$this->getObject('eventpolicy');
        $this->csrf=$this->getObject('nativeauthwebcomposition','security')->build()['csrf'];
    }
    public function requiresLogin($action)
    { return in_array((string)$action,['checkins','manage','edit','savehost','saveevent','schedule','saveschedule','operations','checkin','scan','attendance','moderate','maintain'],true); }
    private function param($name) { $v=$this->getParam($name,''); return is_string($v)?$v:''; }
    private function post()
    { if(($_SERVER['REQUEST_METHOD']??'GET')!=='POST'||!$this->csrf->consume('events',$this->param('csrf_token'))) throw new DomainException('session_expired'); }
    private function input()
    { $input=array_filter($_POST,fn($v)=>is_string($v)); if(isset($_POST['helper_ids'])&&is_array($_POST['helper_ids'])) $input['helpers']=implode(',',array_filter($_POST['helper_ids'],'is_string')); return $input; }
    private function privateHeaders()
    { header('Cache-Control: private, no-store'); header('Referrer-Policy: no-referrer'); header('X-Robots-Tag: noindex, nofollow'); }
    public function dispatch($action)
    {
        $action=(string)$action;
        if(!in_array($action,['','view'],true)) $this->privateHeaders();
        else header('Cache-Control: private, no-store');
        $this->setVar('eventService',$this->service); $this->setVar('eventPolicy',$this->policy); $this->setVar('eventError',''); $this->setVar('eventNotice','');
        try { $template=$this->route($action); }
        catch(DomainException $e) {
            http_response_code($e->getMessage()==='forbidden'?403:400);
            if(str_starts_with($e->getMessage(),'field:')) { [, $field, $message]=explode(':',$e->getMessage(),3); $this->setVar('eventFieldError',[$field=>$message]); $this->setVar('eventError','field_errors'); }
            else $this->setVar('eventError',$e->getMessage());
            // Preserve submitted values even on expired sessions; do not automatically replay writes.
            $this->setVar('eventDraft',$this->input());
            if(in_array($action,['saveevent','savehost'],true)) { $this->setVar('eventRecord',$this->input()); $template='editor_tpl.php'; }
            elseif($action==='checkin'&&in_array($e->getMessage(),['invalid_ticket','already_checked','checkin_window'],true)) { $template=$this->route('scan'); }
            elseif($action==='saveschedule') { $record=$this->service->event($this->param('event_id')); if($record&&$this->policy->canManage($record)) { $this->setVar('eventOccurrence',$this->input()); $template='schedule_tpl.php'; } else $template='error_tpl.php'; }
            elseif($action==='book'&&($o=$this->service->occurrence($this->param('occurrence_id')))&&($e=$this->service->publicEvent($o['event_id']))) {
                $key=$this->param('request_key');
                if(empty($_SESSION['events_forms'][$key])) { $key=bin2hex(random_bytes(32)); $_SESSION['events_forms'][$key]=time(); }
                $this->appendArrayVar('headerParams','<script defer src="'.htmlspecialchars($this->getResourceUri('sharing.js'),ENT_QUOTES,'UTF-8').'"></script>');
                $this->setVar('eventPublic',$e); $this->setVar('eventRequestKey',$key); $this->setVar('eventOffer',$this->param('offer')); $this->setVar('eventCampaign',$this->param('campaign'));
                $template='view_tpl.php';
            }
            else $template='error_tpl.php';
        }
        catch(Throwable $e) {
            error_log('Events request failed: '.get_class($e).' at '.basename($e->getFile()).':'.$e->getLine());
            http_response_code(503); $this->setVar('eventError','temporarily_unavailable'); $this->setVar('eventDraft',$this->input()); $template='error_tpl.php';
        }
        if(in_array($template,['editor_tpl.php','schedule_tpl.php'],true)) $this->appendArrayVar('headerParams','<script defer src="'.htmlspecialchars($this->getResourceUri('editor.js'),ENT_QUOTES,'UTF-8').'"></script>');
        $this->setVar('eventCsrf',$this->csrf->issue('events'));
        return $template;
    }
    private function route($action)
    {
        switch($action) {
            case 'savehost':
                $this->post(); if(!$this->policy->canCreate()) throw new DomainException('forbidden');
                $draft=$this->input();
                $draft['host_reference']=$this->getObject('hostservice','host-service')->create(['create_id'=>$draft['host_create_id']??'','name'=>$draft['host_name']??'','biography'=>$draft['host_biography']??'','image_url'=>$draft['host_image_url']??'']);
                $draft['host']=''; $draft['host_user_id']='';
                unset($draft['host_create_id'],$draft['host_name'],$draft['host_biography'],$draft['host_image_url']);
                $this->setVar('eventRecord',$draft); $this->setVar('eventNotice','host_saved'); return 'editor_tpl.php';
            case 'saveevent':
                $this->post(); $e=$this->service->saveEvent($this->input()); if($this->param('next_step')==='date') { header('Location: '.$this->service->url('schedule',['event_id'=>$e['id']]),true,303); exit; } $this->setVar('eventRecord',$e); $this->setVar('eventNotice','saved'); return 'editor_tpl.php';
            case 'edit':
                $e=$this->param('id')!==''?$this->service->event($this->param('id')):null;
                if($e?!$this->policy->canManage($e):!$this->policy->canCreate()) throw new DomainException('forbidden');
                $this->setVar('eventRecord',$e??[]); return 'editor_tpl.php';
            case 'checkins':
                $this->setVar('eventAssignments',$this->service->checkInAssignments()); return 'checkins_tpl.php';
            case 'manage':
                if(!$this->policy->canCreate()) throw new DomainException('forbidden');
                $this->setVar('eventRecords',$this->service->managed()); return 'manage_tpl.php';
            case 'saveschedule':
                $this->post(); $o=$this->service->saveOccurrence($this->input()); $this->setVar('eventOccurrence',$o); $this->setVar('eventNotice','saved'); return 'schedule_tpl.php';
            case 'schedule':
                $source=$this->param('id')?:$this->param('copy');
                $o=$source!==''?$this->service->occurrence($source):null;
                $e=$this->service->event($o['event_id']??$this->param('event_id'));
                if(!$e||!$this->policy->canManage($e)) throw new DomainException('forbidden');
                if($o&&$this->param('copy')!=='') { unset($o['id']); $o['revision']=0; $o['status']='open'; }
                $this->setVar('eventOccurrence',$o??['event_id'=>$e['id']]); return 'schedule_tpl.php';
            case 'book':
                $this->post(); $this->rateLimit('book',10);
                $key=$this->param('request_key'); if(empty($_SESSION['events_forms'][$key])) throw new DomainException('session_expired');
                $b=$this->service->prepareBooking($this->param('occurrence_id'),$this->input(),$key,$this->param('offer'));
                $token=$this->policy->token('booking',$b['id']); $_SESSION['events_bookings'][$b['id']]=$token;
                $this->setVar('eventBooking',$b); $this->setVar('eventToken',$token); return $this->bookingPage($b,$token);
            case 'checkout':
                $this->post(); $b=$this->service->booking($this->param('token')); if(!$b) throw new DomainException('forbidden');
                $started=$this->service->checkout($b);
                if(!empty($started['ok'])&&!empty($started['approvalUrl'])) { header('Location: '.$started['approvalUrl'],true,303); exit; }
                $this->setVar('eventNotice',!empty($started['ok'])?'checkout_pending':'checkout_unavailable');
                return $this->bookingPage($b,$this->param('token'));
            case 'booking':
                $b=$this->service->booking($this->param('token')); if(!$b) throw new DomainException('forbidden');
                return $this->bookingPage($b,$this->param('token'));
            case 'transfer':
                $this->post(); $b=$this->service->booking($this->param('token')); if(!$b) throw new DomainException('forbidden');
                $this->service->transfer($b,$this->param('ticket_id'),$this->param('attendee')); $this->setVar('eventNotice','transferred'); return $this->bookingPage($b,$this->param('token'));
            case 'ticket':
            case 'calendar':
                $data=$this->service->ticket($this->param('token')); if(!$data) throw new DomainException('invalid_ticket');
                if($action==='calendar') return $this->calendar($data);
                $this->setVar('eventTicketData',$data); $this->setVar('eventToken',$this->param('token'));
                $this->appendArrayVar('headerParams','<script defer src="'.htmlspecialchars($this->getResourceUri('js/qrcode-generator-1.4.4.js','security'),ENT_QUOTES,'UTF-8').'"></script>');
                $this->appendArrayVar('headerParams','<script defer src="'.htmlspecialchars($this->getResourceUri('tickets.js'),ENT_QUOTES,'UTF-8').'"></script>');
                return 'ticket_tpl.php';
            case 'review':
                $this->post(); $this->service->review($this->param('token'),$this->input()); $this->setVar('eventNotice','review_saved'); return 'message_tpl.php';
            case 'waitlist':
                $this->post(); $this->rateLimit('waitlist',5); $this->service->joinWaitlist($this->param('occurrence_id'),$this->input()); $this->setVar('eventNotice','waitlist_saved'); return 'message_tpl.php';
            case 'waitlistconfirm':
                // Email scanners may visit GET links; confirmation requires an explicit POST.
                if(($_SERVER['REQUEST_METHOD']??'GET')==='POST') { $this->post(); $this->service->confirmWaitlist($this->param('token')); $this->setVar('eventNotice','waitlist_confirmed'); return 'message_tpl.php'; }
                $this->setVar('eventToken',$this->param('token')); return 'waitlist_confirm_tpl.php';
            case 'maintain':
            case 'operations':
            case 'attendance':
            case 'scan':
            case 'checkin':
                $o=$this->service->occurrence($this->param('id')); $e=$o?$this->service->event($o['event_id']):null;
                $manager=$e&&$this->policy->canManage($e);
                if(!$e||!$this->policy->canCheckIn($e,$o)) throw new DomainException('forbidden');
                if(in_array($action,['operations','maintain'],true)&&!$manager) throw new DomainException('forbidden');
                if($action==='maintain') { $this->post(); $this->service->maintain($o['id']); $this->setVar('eventNotice','queued'); }
                if($action==='checkin') { $this->post(); $this->service->checkIn($o['id'],$this->param('code')); $this->setVar('eventNotice','checked_in'); }
                if($action==='attendance') return $this->attendance($o);
                $this->setVar('eventOccurrence',$o); $this->setVar('eventRecord',$e);
                if(in_array($action,['scan','checkin'],true)) {
                    $this->setVar('eventTickets',$this->store->rows('tickets',['occurrence_id'=>$o['id']]));
                    $this->appendArrayVar('headerParams','<script defer src="'.htmlspecialchars($this->getResourceUri('scanner.js'),ENT_QUOTES,'UTF-8').'"></script>'); return 'scan_tpl.php';
                }
                $this->setVar('eventBookings',$this->store->rows('bookings',['occurrence_id'=>$o['id']]));
                $this->setVar('eventTickets',$this->store->rows('tickets',['occurrence_id'=>$o['id']]));
                $this->setVar('eventReviews',$this->store->rows('reviews',['event_id'=>$e['id']])); return 'operations_tpl.php';
            case 'moderate':
                $this->post(); $this->service->moderate($this->param('id'),$this->param('status'),$this->param('reason'),$this->param('reply')); $this->setVar('eventNotice','saved'); return 'message_tpl.php';
            case 'view':
                $e=$this->service->publicEvent($this->param('id')); if(!$e) throw new DomainException('not_found');
                $key=bin2hex(random_bytes(32)); $_SESSION['events_forms'][$key]=time();
                foreach($_SESSION['events_forms'] as $k=>$at) if($at<time()-7200) unset($_SESSION['events_forms'][$k]);
                $this->appendArrayVar('headerParams','<script defer src="'.htmlspecialchars($this->getResourceUri('sharing.js'),ENT_QUOTES,'UTF-8').'"></script>');
                $this->setVar('eventPublic',$e); $this->setVar('eventRequestKey',$key); $this->setVar('eventOffer',$this->param('offer')); $this->setVar('eventCampaign',$this->param('utm_source'));
                $escape=fn($v)=>htmlspecialchars($v,ENT_QUOTES,'UTF-8');
                foreach(['og:title'=>$e['title'],'og:description'=>$e['summary'],'og:url'=>$this->service->url('view',['id'=>$e['id']]),'og:image'=>$e['details']['image_url']] as $key=>$value)
                    if($value!=='') $this->appendArrayVar('headerParams','<meta property="'.$key.'" content="'.$escape($value).'">');
                return 'view_tpl.php';
            default: $this->setVar('eventPublicRecords',$this->service->catalogue()); return 'catalogue_tpl.php';
        }
    }
    private function bookingPage(array $b,$token)
    {
        $i=$this->service->intentFor($b);
        // Reconciliation queries the provider; the return URL is never payment evidence.
        if($i&&$i['provider_code']==='paystack'&&in_array($i['state'],['awaiting_approval','processing'],true)) {
            $this->getObject('paymentservice','payment-service')->reconcileIntent($i['id']); $b=$this->service->booking($token);
        }
        $o=$this->service->occurrence($b['occurrence_id']);
        $this->setVar('eventOccurrence',$o); $this->setVar('eventRecord',$this->service->event($o['event_id']));
        $this->setVar('eventBooking',$b); $this->setVar('eventToken',$token); $this->setVar('eventBookingTickets',$this->service->bookingTickets($b)); return 'booking_tpl.php';
    }
    private function calendar(array $data)
    {
        $o=$data['occurrence']; $escape=fn($v)=>str_replace(["\\","\r","\n",',',';'],["\\\\",'',"\\n","\\,","\\;"],$v);
        $lines=['BEGIN:VCALENDAR','VERSION:2.0','PRODID:-//Chisimba//Events//EN','BEGIN:VEVENT','UID:'.$o['id'].'@chisimba-events','DTSTAMP:'.gmdate('Ymd\THis\Z'),'DTSTART:'.gmdate('Ymd\THis\Z',$o['starts_at']),'DTEND:'.gmdate('Ymd\THis\Z',$o['ends_at']),'SUMMARY:'.$escape($data['event']['title']),'DESCRIPTION:'.$escape($this->service->text('private_calendar')),'END:VEVENT','END:VCALENDAR'];
        header('Content-Type: text/calendar; charset=utf-8'); header('Content-Disposition: attachment; filename="event.ics"'); $folded=[]; foreach($lines as $line) { while(strlen($line)>73) { $part=mb_strcut($line,0,73,'UTF-8'); $folded[]=$part; $line=' '.substr($line,strlen($part)); } $folded[]=$line; } echo implode("\r\n",$folded)."\r\n"; exit;
    }
    private function attendance(array $o)
    {
        header('Content-Type: text/csv; charset=utf-8'); header('Content-Disposition: attachment; filename="attendance.csv"'); $f=fopen('php://output','w');
        fputcsv($f,[$this->service->text('attendee'),$this->service->text('ticket_state'),$this->service->text('checked_at')],',','"','');
        foreach($this->store->rows('tickets',['occurrence_id'=>$o['id']]) as $t) {
            $name=$t['attendee']; if(preg_match('/^[=+@\-\t\r]/',$name)) $name="'".$name;
            fputcsv($f,[$name,$t['state'],$t['checked_at']?$this->service->date($t['checked_at'],$o['timezone']):''],',','"','');
        } fclose($f); exit;
    }
    private function rateLimit($key,$limit)
    {
        $now=time(); $times=array_values(array_filter($_SESSION['events_rate'][$key]??[],fn($at)=>$at>$now-3600));
        if(count($times)>=$limit) throw new DomainException('rate_limited'); $times[]=$now; $_SESSION['events_rate'][$key]=$times;
    }
}
