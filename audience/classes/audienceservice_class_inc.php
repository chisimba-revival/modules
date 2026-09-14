<?php
/** Account-independent subscribers, explicit consent and opaque unsubscribe links. @author Derek Keats */
if (empty($GLOBALS['kewl_entry_point_run'])) die('No direct access');
class audienceservice extends dbTable
{
    public function init($tableName=null,$pearDb=null,$errorCallback='globalPearErrorHandler')
    {
        parent::init('tbl_audience_contacts',$pearDb,$errorCallback);
    }

    public static function email($value)
    {
        $value=strtolower(trim((string)$value));
        if(strlen($value)>254 || !filter_var($value,FILTER_VALIDATE_EMAIL)) throw new DomainException('invalid');
        return $value;
    }

    /** Bound both a recipient and a client address; no raw IP addresses are retained. */
    public function checkRate($email,$address,$now=null)
    {
        $now=$now??time();
        foreach([['email:'.$email,3],['client:'.$address,20]] as [$key,$limit]) {
            $id=substr(hash('sha256',$key.'|'.intdiv($now,3600)),0,32);
            $this->query("INSERT INTO tbl_audience_limits (id,attempts,expires_at) VALUES ('$id',1,".($now+7200).") ON DUPLICATE KEY UPDATE attempts=attempts+1");
            $row=$this->getRow('id',$id,'tbl_audience_limits');
            if(!$row || (int)$row['attempts']>$limit) throw new DomainException('try_later');
        }
        $this->query('DELETE FROM tbl_audience_limits WHERE expires_at < '.(int)$now);
    }

    /** Registration calls this inside its transaction. Existing consent is never changed here. */
    public function contact($email,$name)
    {
        $email=self::email($email);
        $row=$this->getRow('email',$email,'tbl_audience_contacts');
        if(!$row) {
            $id=bin2hex(random_bytes(16));
            if($this->insert(['id'=>$id,'email'=>$email,'name'=>mb_substr(trim($name),0,200),
                'state'=>'pending','revision'=>0,'created_at'=>gmdate('Y-m-d H:i:s')],'tbl_audience_contacts')===false) throw new RuntimeException('Contact persistence failed');
            $row=$this->getRow('id',$id,'tbl_audience_contacts');
        }
        return $this->lock($row['id']);
    }

    public function lock($id)
    {
        if(!$this->one($id))return null;
        return $this->getArray("SELECT * FROM tbl_audience_contacts WHERE id='".$id."' FOR UPDATE")[0]??null;
    }

    public function one($id)
    {
        if(!is_string($id)||!preg_match('/^[a-f0-9]{32}$/D',$id))return null;
        return $this->getRow('id',$id,'tbl_audience_contacts')?:null;
    }

    /** A stale confirmation cannot undo a subsequent unsubscribe. */
    public function confirm($id,$revision,$source,$wording)
    {
        $row=$this->one($id);
        if(!$row || (int)$row['revision']!==(int)$revision) return false;
        $this->update('id',$id,['state'=>'subscribed','verified_at'=>gmdate('Y-m-d H:i:s')]);
        $this->recordConsent($id,'subscribe',$source,$wording);
        return true;
    }

    public function unsubscribeToken($contactId)
    {
        if(!$this->one($contactId))throw new RuntimeException('Unknown contact');
        $token=bin2hex(random_bytes(32));
        if($this->insert(['id'=>bin2hex(random_bytes(16)),'contact_id'=>$contactId,
            'token_hash'=>hash('sha256',$token),'created_at'=>gmdate('Y-m-d H:i:s')],'tbl_audience_tokens')===false)throw new RuntimeException('Token persistence failed');
        return $token;
    }

    public function contactForToken($token)
    {
        if(!is_string($token)||!preg_match('/^[a-f0-9]{64}$/D',$token))return null;
        $row=$this->getRow('token_hash',hash('sha256',$token),'tbl_audience_tokens');
        return $row?$this->one($row['contact_id']):null;
    }

    /** Unsubscribe stops general notifications and event email. Registration history remains. */
    public function unsubscribe($token)
    {
        $this->beginTransaction();
        try {
            $row=$this->contactForToken($token);
            if(!$row){$this->rollbackTransaction();return false;}
            $row=$this->lock($row['id']);
            if($row['state']!=='unsubscribed') {
                $this->query("UPDATE tbl_audience_contacts SET state='unsubscribed',revision=revision+1 WHERE id='".$row['id']."'");
                $this->recordConsent($row['id'],'unsubscribe','email_link','Stop subscriber notifications and webinar emails.');
            }
            $this->commitTransaction();return true;
        }catch(Throwable $error){$this->rollbackTransaction();throw $error;}
    }

    private function recordConsent($id,$action,$source,$wording)
    {
        if($this->insert(['id'=>bin2hex(random_bytes(16)),'contact_id'=>$id,'action'=>$action,
            'source'=>$source,'recorded_at'=>gmdate('Y-m-d H:i:s'),'wording'=>$wording],'tbl_audience_consent')===false)throw new RuntimeException('Consent persistence failed');
    }
}
