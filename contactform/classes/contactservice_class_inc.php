<?php
if(empty($GLOBALS['kewl_entry_point_run']))die('No direct access');
class contactservice extends ChisimbaObject
{
    private $store;
    public function init(){$this->store=$this->getObject('contactstore','contactform');}
    private function config($key,$default=''){return (string)$this->getObject('dbsysconfig','sysconfig')->getValue($key,'contactform',$default);}
    public function mode(){return $this->config('CONTACT_MODE','disabled');}
    public function canManage(){return $this->getObject('user','security')->isLoggedIn()&&$this->getObject('user','security')->isAdmin();}
    public function recent($page=1,$folder='inbox'){if(!$this->canManage())throw new DomainException('forbidden');return $this->store->recent($page,$folder);}
    public static function validate(array $in)
    {
        foreach(['id','name','email','subject','message','website'] as $key)if(!is_string($in[$key]??null))throw new DomainException('invalid');
        if(!preg_match('/^[a-f0-9]{32}$/D',$in['id'])||$in['website']!=='')throw new DomainException('invalid');
        foreach(['name','email','subject','message'] as $key)$in[$key]=trim($in[$key]);
        if($in['name']===''||mb_strlen($in['name'])>150||preg_match('/[\r\n\x00-\x1f]/',$in['name'])||strlen($in['email'])>254||!filter_var($in['email'],FILTER_VALIDATE_EMAIL)||preg_match('/[\r\n\x00]/',$in['email']))throw new DomainException('invalid');
        if($in['subject']===''||mb_strlen($in['subject'])>200||preg_match('/[\r\n\x00-\x1f]/',$in['subject'])||mb_strlen($in['message'])<5||mb_strlen($in['message'])>10000||str_contains($in['message'],"\0"))throw new DomainException('invalid');
        return array_intersect_key($in,array_flip(['id','name','email','subject','message']));
    }
    public function submit(array $input,$address)
    {
        $address=$this->getObject('contactguard','contactform')->check($input);
        $row=self::validate($input);$mode=$this->mode();
        $this->loadClass('submissioncontentpolicy','abuseprotection');
        $reason=SubmissionContentPolicy::reviewReason($row['name'],$row['subject'],$row['message']);
        if(!in_array($mode,['preview','live'],true))throw new DomainException('unavailable');
        $key=$this->config('CONTACT_RATE_KEY');if(strlen($key)<32)throw new DomainException('unavailable');
        if($mode==='live')foreach(['CONTACT_TO','CONTACT_FROM'] as $field)if(!filter_var($this->config($field),FILTER_VALIDATE_EMAIL)||preg_match('/[\r\n]/',$this->config($field)))throw new DomainException('unavailable');
        $row['fingerprint']=hash('sha256',json_encode(array_intersect_key($row,array_flip(['name','email','subject','message'])),JSON_UNESCAPED_UNICODE));
        $row['status']=$reason!==''?'quarantined':($mode==='preview'?'preview':'pending');$row['datecreated']=$row['datemodified']=gmdate('Y-m-d H:i:s');
        $bucket=(int)floor(time()/3600);$rateKey=hash_hmac('sha256',(string)$address.'|'.$bucket,$key);
        if ($this->store->blocked($this->senderHash($row['email']))) return 'sent';
        $saved=$this->store->accept($row,$rateKey,$bucket,$reason);
        if($mode==='live'&&$saved['status']==='pending')$this->deliver($saved['id']);
        return $saved['status']==='quarantined'?'sent':$this->store->find($saved['id'])['status'];
    }
    private function senderHash($email)
    {
        $key=$this->config('CONTACT_RATE_KEY');
        if(strlen($key)<32) throw new DomainException('unavailable');
        return hash_hmac('sha256','sender|'.strtolower(trim($email)),$key);
    }
    public function moderate(array $ids,$operation)
    {
        if(!$this->canManage()) throw new DomainException('forbidden');
        if(!in_array($operation,['spam','trash','restore','block','unblock'],true)||!$ids||count($ids)>20) throw new DomainException('invalid');
        foreach($ids as $id) if(!is_string($id)||!preg_match('/^[a-f0-9]{32}$/D',$id)) throw new DomainException('invalid');
        $actor=(string)$this->getObject('user','security')->userId();
        $this->store->transaction(function()use($ids,$operation,$actor){
            foreach(array_unique($ids) as $id){
                $row=$this->store->find($id,true);if(!$row) throw new DomainException('invalid');
                if($operation==='block') $this->store->block($this->senderHash($row['email']));
                if($operation==='unblock') $this->store->unblock($this->senderHash($row['email']));
                $folder=in_array($operation,['restore','unblock'],true)?'inbox':($operation==='trash'?'trash':'spam');
                $this->store->moderate($id,$folder,'manual',$actor);
            }
        });
    }
    public function reviewPage($page)
    {
        if(!$this->canManage()) throw new DomainException('forbidden');
        $this->loadClass('submissioncontentpolicy','abuseprotection');
        foreach(array_slice($this->store->recent($page,'inbox'),0,20) as $row){
            // A human-restored message is not repeatedly quarantined.
            if($row['reason']==='manual') continue;
            $reason=SubmissionContentPolicy::reviewReason($row['name'],$row['subject'],$row['message']);
            if($this->store->blocked($this->senderHash($row['email'])))$reason='blocked_sender';
            if($reason!=='')$this->store->transaction(function()use($row,$reason){
                $this->store->find($row['id'],true);
                $current=$this->store->review($row['id']);
                if($current['folder']!=='inbox'||$current['reason']==='manual')return;
                $this->store->moderate($row['id'],'spam',$reason,'automatic');
            });
        }
    }
    private function deliver($id)
    {
        $row=$this->store->claim($id);if(!$row)return;
        // A crash/ambiguous transport result must never trigger an automatic repeat send.
        $status='uncertain';
        try{
            $body=$row['name']." <".$row['email'].">\n\n".$row['message']."\n\nReference: ".$row['id'];
            if($this->getObject('plainmailservice','mail')->send($this->config('CONTACT_TO'),$this->config('CONTACT_FROM'),$row['email'],$row['subject'],$body))$status='sent';
        }catch(Throwable $error){}
        $this->store->state($id,$status);
    }
}
