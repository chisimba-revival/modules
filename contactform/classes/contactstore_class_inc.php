<?php
if(empty($GLOBALS['kewl_entry_point_run']))die('No direct access');
class contactstore extends dbTable
{
    public function init($tableName=null,$pearDb=null,$errorCallback='globalPearErrorCallback'){parent::init('tbl_contactform_messages',$pearDb,$errorCallback);}

    public function execute($sql,array $params=[]) {return preg_match('/^SELECT\b/i',ltrim($sql))?$this->selectPrepared($sql,$params):$this->executeExactPrepared($sql,$params);}

    public function find($id,$lock=false){$r=$this->selectPrepared('SELECT * FROM tbl_contactform_messages WHERE id=?'.($lock?' FOR UPDATE':''),[$id]);return $r[0]??null;}
    public function accept(array $row,$rateKey,$bucket,$reason='')
    {
        return $this->transaction(function()use($row,$rateKey,$bucket,$reason){
            $this->executePrepared('INSERT INTO tbl_contactform_limits (id,bucket,total) VALUES (?,'.(int)$bucket.',0) ON DUPLICATE KEY UPDATE id=id',[$rateKey]);
            $rate=$this->selectPrepared('SELECT total FROM tbl_contactform_limits WHERE id=? FOR UPDATE',[$rateKey]);
            $old=$this->find($row['id'],true);
            if($old){if(!hash_equals($old['fingerprint'],$row['fingerprint']))throw new DomainException('conflict');return $old;}
            if((int)$rate[0]['total']>=5)throw new DomainException('limited');
            $this->addMessage($row);
            if ($reason !== '') $this->moderate($row['id'], 'spam', $reason, 'automatic');
            $this->executePrepared('UPDATE tbl_contactform_limits SET total=total+1 WHERE id=?',[$rateKey]);
            $this->execute('DELETE FROM tbl_contactform_limits WHERE bucket<'.((int)$bucket-48));
            return $this->find($row['id']);
        });
    }
    public function addMessage(array $row) {
        $fields=['id','name','email','subject','message','fingerprint','status','datecreated','datemodified'];
        $values=[];foreach($fields as $field)$values[]=$row[$field];
        $this->executeExactPrepared('INSERT INTO tbl_contactform_messages ('.implode(',',$fields).') VALUES ('.implode(',',array_fill(0,count($fields),'?')).')',$values);
    }
    public function claim($id)
    {return $this->transaction(function()use($id){$r=$this->find($id,true);if(!$r||$r['status']!=='pending'||$this->review($id)['folder']!=='inbox')return null;$this->state($id,'sending');return $r;});}
    public function state($id,$status){$this->executePrepared('UPDATE tbl_contactform_messages SET status=?,datemodified=? WHERE id=?',[$status,gmdate('Y-m-d H:i:s'),$id]);}

    public function recent($page=1,$folder='inbox')
    {
        if (!in_array($folder,['inbox','spam','trash'],true)) throw new DomainException('invalid');
        return $this->selectPrepared("SELECT m.*,COALESCE(r.folder,'inbox') AS folder,COALESCE(r.reason,'') AS reason FROM tbl_contactform_messages m LEFT JOIN tbl_contactform_review r ON r.id=m.id WHERE COALESCE(r.folder,'inbox')=?".' ORDER BY m.datecreated DESC,m.id DESC LIMIT 21 OFFSET '.((max(1,min(10000,(int)$page))-1)*20),[$folder]);
    }
    public function review($id)
    {
        $rows=$this->selectPrepared('SELECT folder,reason FROM tbl_contactform_review WHERE id=?',[$id]);
        return $rows[0]??['folder'=>'inbox','reason'=>''];
    }
    public function moderate($id,$folder,$reason,$actor)
    {
        $this->executePrepared('INSERT INTO tbl_contactform_review (id,folder,reason,actor,datemodified) VALUES (?,?,?,?,?) ON DUPLICATE KEY UPDATE folder=VALUES(folder),reason=VALUES(reason),actor=VALUES(actor),datemodified=VALUES(datemodified)',[$id,$folder,$reason,$actor,gmdate('Y-m-d H:i:s')]);
    }
    public function blocked($hash){return (bool)$this->selectPrepared('SELECT id FROM tbl_contactform_blocks WHERE id=?',[$hash]);}
    public function block($hash){$this->executePrepared('INSERT INTO tbl_contactform_blocks (id,datecreated) VALUES (?,?) ON DUPLICATE KEY UPDATE id=id',[$hash,gmdate('Y-m-d H:i:s')]);}
    public function unblock($hash){$this->executePrepared('DELETE FROM tbl_contactform_blocks WHERE id=?',[$hash]);}
}
