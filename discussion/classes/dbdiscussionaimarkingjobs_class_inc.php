<?php
/** Durable AI suggestion queue for marked Discussions. @author Derek Keats */
if(empty($GLOBALS['kewl_entry_point_run'])){die('You cannot view this page directly');}
class dbdiscussionaimarkingjobs extends dbTable
{
    private $clock;
    public function init($tableName=null,$pearDb=null,$errorCallback='globalPearErrorHandler'){parent::init('tbl_discussion_ai_marking_jobs');$this->clock=$this->getObject('timeanddateservice','timeanddate-service');}
    public function enqueue($context,$discussionId,$studentId,$requesterId)
    {
        $active=$this->getArray("SELECT id FROM tbl_discussion_ai_marking_jobs WHERE discussion_id='".$this->e($discussionId)."' AND student_id='".$this->e($studentId)."' AND status IN ('queued','running') LIMIT 1");if(!empty($active[0]['id']))return $active[0]['id'];
        $id=bin2hex(random_bytes(16));$now=$this->clock->nowStorage();$evidence=$this->getObject('dbpost','discussion')->getAssessmentEvidence($discussionId,$studentId);
        $this->insert(array('id'=>$id,'contextcode'=>(string)$context,'discussion_id'=>(string)$discussionId,'student_id'=>(string)$studentId,'requester_id'=>(string)$requesterId,'status'=>'queued','rubric_version'=>'discussion-participation-quality-v1','evidence_json'=>json_encode($evidence),'result_json'=>'{}','date_created'=>$now,'date_updated'=>$now));return $id;
    }
    public function getScoped($id,$context,$requesterId,$admin=false){$row=$this->getRow('id',(string)$id);if(!is_array($row)||(string)$row['contextcode']!==(string)$context||(!$admin&&(string)$row['requester_id']!==(string)$requesterId))return false;$row['suggestion']=json_decode((string)$row['result_json'],true)?:array();return $row;}
    public function latest($discussionId,$studentId,$context,$requesterId,$admin=false){$sql="SELECT id FROM tbl_discussion_ai_marking_jobs WHERE discussion_id='".$this->e($discussionId)."' AND student_id='".$this->e($studentId)."' AND contextcode='".$this->e($context)."' AND status='completed'".($admin?'':" AND requester_id='".$this->e($requesterId)."'")." ORDER BY date_completed DESC LIMIT 1";$rows=$this->getArray($sql);return empty($rows[0]['id'])?false:$this->getScoped($rows[0]['id'],$context,$requesterId,$admin);}
    public function runOne()
    {
        $rows=$this->getArray("SELECT * FROM tbl_discussion_ai_marking_jobs WHERE status='queued' ORDER BY date_created ASC LIMIT 1");if(empty($rows[0]))return array('selected'=>0);$row=$rows[0];$now=$this->clock->nowStorage();$this->update('id',$row['id'],array('status'=>'running','date_updated'=>$now));$discussion=$this->getObject('dbdiscussion','discussion')->getDiscussion($row['discussion_id']);$evidence=json_decode((string)$row['evidence_json'],true);if(!is_array($evidence)){$evidence=array();}$rubric=$this->getObject('discussiondefaultrubric','discussion')->getStructuredRubric();$out=is_array($discussion)&&is_array($rubric)?$this->getObject('discussionaimarker','discussion')->suggest($discussion,$evidence,$rubric):array('ok'=>false,'error'=>'resource_unavailable');$done=$this->clock->nowStorage();$ok=!empty($out['ok']);$this->update('id',$row['id'],array('status'=>$ok?'completed':'failed','result_json'=>json_encode($out['suggestion']??array()),'error_code'=>$ok?null:(string)($out['error']??'provider_failed'),'date_updated'=>$done,'date_completed'=>$done));return array('selected'=>1,'completed'=>$ok?1:0,'failed'=>$ok?0:1,'jobId'=>$row['id']);
    }
    private function e($value){return str_replace("'","''",(string)$value);}
}
