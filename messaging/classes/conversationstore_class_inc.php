<?php
/** Transactional persistence for context conversations; legacy records are separate. @author Derek Keats */
if (empty($GLOBALS['kewl_entry_point_run'])) die('No direct access');
class conversationstore extends dbTable
{
    public function init($tableName=null,$pearDb=null,$errorCallback='globalPearErrorCallback') { parent::init('tbl_messaging_channels_v2',$pearDb,$errorCallback); }
    public function channel($id) {return $this->selectPrepared('SELECT * FROM tbl_messaging_channels_v2 WHERE id=?',[$id])[0]??null;}
    public function create($context) {
        $id=substr(hash('sha256','context-discussion:'.$context),0,32);
        $this->executePrepared('INSERT INTO tbl_messaging_channels_v2 (id,context_code,last_sequence) VALUES (?,?,0) ON DUPLICATE KEY UPDATE id=id',[$id,$context]);
        return $id;
    }
    public function locked($id,callable $operation) {
        return $this->transactionWithSavepoint(function()use($id,$operation){
            $channel=$this->selectPrepared('SELECT * FROM tbl_messaging_channels_v2 WHERE id=? FOR UPDATE',[$id])[0]??null;
            if(!$channel)throw new DomainException('unavailable');
            return $operation($channel);
        });
    }
    public function banned($channel,$user) {return (bool)$this->selectPrepared('SELECT id FROM tbl_messaging_bans_v2 WHERE channel_id=? AND user_id=?',[$channel,$user]);}
    public function receipt($channel,$user,$key) {return $this->selectPrepared('SELECT * FROM tbl_messaging_posts_v2 WHERE channel_id=? AND sender_id=? AND submission_id=?',[$channel,$user,$key])[0]??null;}
    public function append($channel,$actor,$key,$body,$sequence) {
        $row=['id'=>bin2hex(random_bytes(16)),'channel_id'=>$channel,'sender_id'=>$actor,'submission_id'=>$key,'body'=>$body,'sequence_no'=>$sequence,'created_at'=>gmdate('Y-m-d H:i:s')];
        $this->executePrepared('INSERT INTO tbl_messaging_posts_v2 (id,channel_id,sender_id,submission_id,body,sequence_no,created_at) VALUES (?,?,?,?,?,?,?)',array_values($row));
        $this->executePrepared('UPDATE tbl_messaging_channels_v2 SET last_sequence=? WHERE id=?',[$sequence,$channel]);
        return $row;
    }
    public function history($channel,$before,$limit) {
        return array_reverse($this->selectPrepared('SELECT * FROM tbl_messaging_posts_v2 WHERE channel_id=? AND sequence_no<? ORDER BY sequence_no DESC LIMIT '.(int)$limit,[$channel,$before]));
    }
    public function markRead($channel,$actor,$sequence) {
        $id=substr(hash('sha256',$channel.':'.$actor),0,32);
        $this->executePrepared('INSERT INTO tbl_messaging_reads_v2 (id,channel_id,user_id,sequence_no) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE sequence_no=GREATEST(sequence_no,VALUES(sequence_no))',[$id,$channel,$actor,$sequence]);
    }
    public function readPosition($channel,$actor) {return (int)($this->selectPrepared('SELECT sequence_no FROM tbl_messaging_reads_v2 WHERE channel_id=? AND user_id=?',[$channel,$actor])[0]['sequence_no']??0);}
}
