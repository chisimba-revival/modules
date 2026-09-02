<?php
/** Durable Essay AI-marking suggestion queue. @package essay */
if (empty($GLOBALS['kewl_entry_point_run'])) { die('You cannot view this page directly'); }
/** Stores retained lecturer-owned marking drafts. @author Derek Keats */
class dbessayaimarkingjobs extends dbTable
{
    public $objTimeAndDate;
    public function init($tableName=null,$pearDb=null,$errorCallback='globalPearErrorHandler')
    {
        parent::init($tableName !== null ? $tableName : 'tbl_essay_ai_marking_jobs',$pearDb,$errorCallback);
        $this->objTimeAndDate=$this->getObject('timeanddateservice','timeanddate-service');
    }
    public function enqueue($context,$userId,$bookingId)
    {
        $active=$this->getArray("SELECT id FROM tbl_essay_ai_marking_jobs WHERE booking_id='".$this->escapeValue($bookingId)."' AND status IN ('queued','running') ORDER BY date_created DESC LIMIT 1");
        if (isset($active[0]['id'])) { return $active[0]['id']; }
        $id=bin2hex(random_bytes(16));$now=$this->objTimeAndDate->nowStorage();
        $this->insert(array('id'=>$id,'contextcode'=>(string)$context,'userid'=>(string)$userId,'booking_id'=>(string)$bookingId,'status'=>'queued','result_json'=>'{}','error_code'=>null,'date_created'=>$now,'date_updated'=>$now,'date_completed'=>null));return $id;
    }
    public function getOwned($id,$context,$userId,$admin=false)
    {
        $row=$this->getRow('id',(string)$id);if(!is_array($row)||(string)$row['contextcode']!==(string)$context||(!$admin&&(string)$row['userid']!==(string)$userId)){return false;}
        $decoded=json_decode((string)($row['result_json']??'{}'),true);$row['suggestion']=is_array($decoded)?$decoded:array();return $row;
    }
    public function getLatestCompleted($bookingId,$context,$userId,$admin=false)
    {
        $sql="SELECT id FROM tbl_essay_ai_marking_jobs WHERE booking_id='".$this->escapeValue($bookingId)."' AND contextcode='".$this->escapeValue($context)."' AND status='completed'";if(!$admin){$sql.=" AND userid='".$this->escapeValue($userId)."'";}
        $rows=$this->getArray($sql.' ORDER BY date_completed DESC,date_created DESC LIMIT 1');return empty($rows[0]['id'])?false:$this->getOwned($rows[0]['id'],$context,$userId,$admin);
    }
    public function listForTopic($topicId,$context,$userId,$admin=false)
    {
        $sql="SELECT j.id,j.booking_id,j.status,j.error_code,j.date_updated FROM tbl_essay_ai_marking_jobs j INNER JOIN tbl_essay_book b ON b.id=j.booking_id WHERE b.topicid='".$this->escapeValue($topicId)."' AND j.contextcode='".$this->escapeValue($context)."'";if(!$admin){$sql.=" AND j.userid='".$this->escapeValue($userId)."'";}$sql.=' ORDER BY j.date_created DESC';
        $latest=array();foreach((array)$this->getArray($sql) as $row){$key=(string)$row['booking_id'];if(!isset($latest[$key])){$latest[$key]=$row;}}return $latest;
    }
    public function runOne()
    {
        $stale=$this->objTimeAndDate->toStorage($this->objTimeAndDate->nowUtc()->modify('-15 minutes'));$rows=$this->getArray("SELECT * FROM tbl_essay_ai_marking_jobs WHERE status='queued' OR (status='running' AND date_updated<'".$stale."') ORDER BY date_created ASC LIMIT 1");if(empty($rows[0]))return array('selected'=>0);
        $row=$rows[0];$this->update('id',$row['id'],array('status'=>'running','date_updated'=>$this->objTimeAndDate->nowStorage()));$bookings=$this->getObject('dbessay_book','essay');$topics=$this->getObject('dbessay_topics','essay');$essays=$this->getObject('dbessays','essay');$booking=$bookings->getRow('id',$row['booking_id']);$out=array('ok'=>false,'error'=>'submission_unavailable');
        if(is_array($booking)&&(string)$booking['context']===(string)$row['contextcode']&&!empty($booking['submitdate'])){$topicRows=$topics->getTopic($booking['topicid']);$essayRows=$essays->getEssay($booking['essayid']);if(isset($topicRows[0],$essayRows[0])){$rubric=$this->getObject('essaydefaultrubric','essay')->getStructuredRubric();$out=is_array($rubric)?$this->getObject('essayaimarker','essay')->suggest($topicRows[0],$essayRows[0],$booking,$rubric):array('ok'=>false,'error'=>'rubric_unavailable');}}
        $done=$this->objTimeAndDate->nowStorage();$ok=!empty($out['ok']);$this->update('id',$row['id'],array('status'=>$ok?'completed':'failed','result_json'=>json_encode($out['suggestion']??array()),'error_code'=>$ok?null:(string)($out['error']??'provider_failed'),'date_updated'=>$done,'date_completed'=>$done));return array('selected'=>1,'completed'=>$ok?1:0,'failed'=>$ok?0:1,'jobId'=>$row['id']);
    }
    /** Escape a value for the legacy database abstraction's read queries. */
    private function escapeValue($value){return str_replace("'","''",(string)$value);}
}
?>
