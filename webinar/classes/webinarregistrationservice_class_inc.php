<?php
/** Account-free registration, verified consent and idempotent event mail. @author Derek Keats */
if(empty($GLOBALS['kewl_entry_point_run']))die('No direct access');
class webinarregistrationservice extends ChisimbaObject
{
    private $audience; private $registrations; private $records; private $mail;
    public function init()
    {
        $this->audience=$this->getObject('audienceservice','audience');
        $this->registrations=$this->getObject('webinarregistrations');
        $this->records=$this->getObject('webinarstore');
        $this->mail=$this->getObject('communicationservice','communications');
        $this->loadClass('webinarschedule','webinar');
    }
    private function text($key){return $this->getObject('webinarrenderer')->text($key);}
    private function url($action,array $params=[])
    {return rtrim($this->getObject('altconfig','config')->getSiteRoot(),'/').'/index.php?'.http_build_query(['module'=>'webinar','action'=>$action]+$params);}

    public function register(array $record,$name,$email,$consent,$client)
    {
        if(!webinarschedule::canRegister($record))throw new DomainException('closed');
        if(!$consent || trim($name)==='' || mb_strlen($name)>200)throw new DomainException('invalid');
        $email=audienceservice::email($email);
        $this->audience->checkRate($email,$client);
        $this->registrations->beginTransaction();
        try {
            $contact=$this->audience->contact($email,$name);
            $existing=$this->registrations->forContact($record['id'],$contact['id']);
            if($existing && (int)$existing['contact_revision']===(int)$contact['revision']
                && (($existing['state']==='confirmed' && $contact['state']==='subscribed')
                    || ($existing['state']==='pending' && (int)$existing['expires_at']>time()))) {
                $this->registrations->commitTransaction();return;
            }
            $token=bin2hex(random_bytes(32));
            $row=['id'=>$existing['id']??bin2hex(random_bytes(16)),'webinar_id'=>$record['id'],
                'contact_id'=>$contact['id'],'state'=>'pending','confirm_hash'=>hash('sha256',$token),
                'expires_at'=>time()+86400,'contact_revision'=>(int)$contact['revision'],'created_at'=>gmdate('Y-m-d H:i:s'),'confirmed_at'=>null];
            $saved=$existing?$this->registrations->update('id',$row['id'],$row):$this->registrations->insert($row);
            if($saved===false)throw new RuntimeException('Registration persistence failed');
            $this->queue($row,$record,$contact,'verify',$token);
            $this->registrations->commitTransaction();
        }catch(Throwable $error){$this->registrations->rollbackTransaction();throw $error;}
    }

    public function confirm($token)
    {
        $this->registrations->beginTransaction();
        try {
            $r=$this->registrations->token($token);
            if(!$r)throw new DomainException('expired');
            $contact=$this->audience->lock($r['contact_id']);
            $r=$this->registrations->token($token);
            $record=$this->records->one($r['webinar_id']);
            if(!$contact || !$record || (int)$r['expires_at']<time() || (int)$r['contact_revision']!==(int)$contact['revision'])throw new DomainException('expired');
            if($r['state']==='confirmed'){$this->registrations->commitTransaction();return $record;}
            if($r['state']!=='pending'||!webinarschedule::canRegister($record))throw new DomainException('closed');
            if(!$this->audience->confirm($contact['id'],$r['contact_revision'],'webinar:'.$record['id'],$this->text('consent')))throw new DomainException('expired');
            $this->registrations->update('id',$r['id'],['state'=>'confirmed','confirmed_at'=>gmdate('Y-m-d H:i:s')]);
            $r['state']='confirmed';$contact=$this->audience->one($contact['id']);
            $this->queue($r,$record,$contact,'confirmed');
            $this->registrations->commitTransaction();return $record;
        }catch(Throwable $error){$this->registrations->rollbackTransaction();throw $error;}
    }

    public function queue(array $registration,array $record,array $contact,$kind,$token='')
    {
        $start=webinarschedule::start($record);
        if(!$start)throw new RuntimeException('Missing webinar time zone');
        $link=$kind==='verify'?$this->url('confirm',['token'=>$token]):$this->url('view',['id'=>$record['id']]);
        $key='webinar:'.$registration['id'].':'.$kind.':'.($kind==='verify'?$registration['confirm_hash']:$registration['contact_revision'].':'.$start->getTimestamp());
        $existing=$this->mail->messageForKey($key);if($existing)return $existing;
        $unsubscribe=$this->url('unsubscribe',['token'=>$this->audience->unsubscribeToken($contact['id'])]);
        $values=['{title}'=>$record['title'],'{date}'=>$start->format('j F Y, H:i').' '.$start->getTimezone()->getName(),'{url}'=>$link,'{unsubscribe}'=>$unsubscribe];
        $result=$this->mail->queueEmail(['to'=>$contact['email'],'toName'=>$contact['name'],
            'subject'=>strtr($this->text('email_'.$kind.'_subject'),$values),
            'text'=>strtr($this->text('email_'.$kind.'_body')."\n\n".$this->text('email_unsubscribe'),$values),
            'idempotencyKey'=>$key,'metadata'=>['policy_module'=>'webinar','registration_id'=>$registration['id'],
                'kind'=>$kind,'revision'=>(int)$registration['contact_revision'],'confirm_hash'=>$registration['confirm_hash'],
                'starts_at'=>$start->getTimestamp()]]);
        if(empty($result['ok']))throw new RuntimeException('Email could not be queued');
        return $result;
    }

    /** One scheduler process is used; outbox keys prevent repeat reminder delivery. */
    public function reminders($now=null)
    {
        $now=$now??time();$count=0;
        foreach($this->records->published('webinar') as $record){
            if(!webinarschedule::canRegister($record,$now))continue;
            foreach(['morning','ninety'] as $kind){
                $due=webinarschedule::reminderDue($record,$kind);
                if($due===null||$due>$now||$now-$due>3600)continue;
                foreach($this->registrations->confirmed($record['id']) as $r){
                    $contact=$this->audience->one($r['contact_id']);
                    if(!$contact||$contact['state']!=='subscribed'||(int)$contact['revision']!==(int)$r['contact_revision']
                        ||strtotime($r['confirmed_at'].' UTC')>$due)continue;
                    $this->queue($r,$record,$contact,$kind);$count++;
                }
            }
        }
        return ['eligible'=>$count];
    }
}
