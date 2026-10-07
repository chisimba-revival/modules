<?php
if(empty($GLOBALS['kewl_entry_point_run']))die('No direct access');
class contactstore extends dbTable
{
    public function init($tableName=null,$pearDb=null,$errorCallback='globalPearErrorCallback'){parent::init('tbl_contactform_messages',$pearDb,$errorCallback);}
    private function q($value){return $value===''?"''":$this->objEngine->getDbObj()->quote((string)$value);}
    public function execute($sql){$r=$this->query($sql);if($r===false||PEAR::isError($r))throw new RuntimeException('failed');return $r;}
    public function transaction(callable $fn)
    {
        $db=$this->objEngine->getDbObj();
        if(!$db->supports('transactions')||!empty($db->in_transaction))throw new RuntimeException('Contact transaction unavailable');
        $r=$db->beginTransaction();if($r===false||PEAR::isError($r))throw new RuntimeException('Contact transaction failed');
        try{$result=$fn();$r=$db->commit();if($r===false||PEAR::isError($r))throw new RuntimeException('Contact commit failed');return $result;}
        catch(Throwable $e){$db->rollback();throw $e;}
    }
    public function find($id,$lock=false){$r=$this->execute('SELECT * FROM tbl_contactform_messages WHERE id='.$this->q($id).($lock?' FOR UPDATE':''));return $r[0]??null;}
    public function accept(array $row,$rateKey,$bucket,$reason='')
    {
        return $this->transaction(function()use($row,$rateKey,$bucket,$reason){
            $this->execute('INSERT INTO tbl_contactform_limits (id,bucket,total) VALUES ('.$this->q($rateKey).','.(int)$bucket.',0) ON DUPLICATE KEY UPDATE id=id');
            $rate=$this->execute('SELECT total FROM tbl_contactform_limits WHERE id='.$this->q($rateKey).' FOR UPDATE');
            $old=$this->find($row['id'],true);
            if($old){if(!hash_equals($old['fingerprint'],$row['fingerprint']))throw new DomainException('conflict');return $old;}
            if((int)$rate[0]['total']>=5)throw new DomainException('limited');
            $this->addMessage($row);
            if ($reason !== '') $this->moderate($row['id'], 'spam', $reason, 'automatic');
            $this->execute('UPDATE tbl_contactform_limits SET total=total+1 WHERE id='.$this->q($rateKey));
            $this->execute('DELETE FROM tbl_contactform_limits WHERE bucket<'.((int)$bucket-48));
            return $this->find($row['id']);
        });
    }
    public function addMessage(array $row)
    {
        $fields=['id','name','email','subject','message','fingerprint','status','datecreated','datemodified'];
        $values=[];foreach($fields as $field)$values[]=$this->q($row[$field]);
        $this->execute('INSERT INTO tbl_contactform_messages ('.implode(',',$fields).') VALUES ('.implode(',',$values).')');
    }
    public function claim($id)
    {return $this->transaction(function()use($id){$r=$this->find($id,true);if(!$r||$r['status']!=='pending'||$this->review($id)['folder']!=='inbox')return null;$this->state($id,'sending');return $r;});}
    public function state($id,$status){$this->execute('UPDATE tbl_contactform_messages SET status='.$this->q($status).',datemodified='.$this->q(gmdate('Y-m-d H:i:s')).' WHERE id='.$this->q($id));}

    public function recent($page=1,$folder='inbox')
    {
        if (!in_array($folder,['inbox','spam','trash'],true)) throw new DomainException('invalid');
        return $this->execute("SELECT m.*,COALESCE(r.folder,'inbox') AS folder,COALESCE(r.reason,'') AS reason FROM tbl_contactform_messages m LEFT JOIN tbl_contactform_review r ON r.id=m.id WHERE COALESCE(r.folder,'inbox')=".$this->q($folder).' ORDER BY m.datecreated DESC,m.id DESC LIMIT 21 OFFSET '.((max(1,min(10000,(int)$page))-1)*20));
    }
    public function review($id)
    {
        $rows=$this->execute('SELECT folder,reason FROM tbl_contactform_review WHERE id='.$this->q($id));
        return $rows[0]??['folder'=>'inbox','reason'=>''];
    }
    public function moderate($id,$folder,$reason,$actor)
    {
        $this->execute('INSERT INTO tbl_contactform_review (id,folder,reason,actor,datemodified) VALUES ('.$this->q($id).','.$this->q($folder).','.$this->q($reason).','.$this->q($actor).','.$this->q(gmdate('Y-m-d H:i:s')).') ON DUPLICATE KEY UPDATE folder=VALUES(folder),reason=VALUES(reason),actor=VALUES(actor),datemodified=VALUES(datemodified)');
    }
    public function blocked($hash){return (bool)$this->execute('SELECT id FROM tbl_contactform_blocks WHERE id='.$this->q($hash));}
    public function block($hash){$this->execute('INSERT INTO tbl_contactform_blocks (id,datecreated) VALUES ('.$this->q($hash).','.$this->q(gmdate('Y-m-d H:i:s')).') ON DUPLICATE KEY UPDATE id=id');}
    public function unblock($hash){$this->execute('DELETE FROM tbl_contactform_blocks WHERE id='.$this->q($hash));}
}
