<?php
/** Discussion-owned manual assessment marks. @author Derek Keats */
if (!$GLOBALS['kewl_entry_point_run']) { die('You cannot view this page directly'); }
class dbdiscussionassessmentmarks extends dbtable
{
    public function init($tableName = null, $pearDb = null, $errorCallback = 'globalPearErrorHandler')
    {
        parent::init('tbl_discussion_assessment_marks');
    }
    public function findMark($discussionId, $userId)
    {
        $rows = $this->getAll("WHERE discussion_id='".addslashes($discussionId)."' AND user_id='".addslashes($userId)."' LIMIT 1");
        return is_array($rows) && $rows ? $rows[0] : false;
    }
    public function saveMark($discussionId,$userId,$mark,$feedback,$markerId,$rubricJson,$aiJobId=null)
    {
        $now=$this->getObject('timeanddateservice','timeanddate-service')->nowStorage();$existing=$this->findMark($discussionId,$userId);
        $data=array('discussion_id'=>(string)$discussionId,'user_id'=>(string)$userId,'mark'=>(float)$mark,'feedback'=>(string)$feedback,'marker_id'=>(string)$markerId,'rubric_json'=>(string)$rubricJson,'ai_job_id'=>$aiJobId,'date_updated'=>$now);
        if($existing){return $this->update('id',$existing['id'],$data);}$data['id']=bin2hex(random_bytes(16));$data['date_created']=$now;return $this->insert($data);
    }
    public function getForDiscussion($discussionId){$rows=$this->getAll("WHERE discussion_id='".addslashes($discussionId)."'");$out=array();foreach((array)$rows as $row){$out[(string)$row['user_id']]=$row;}return $out;}
}
