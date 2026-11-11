<?php
/** Read-only activity/result adapter. Gradebook owns plan weighting. @author Derek Keats */
if (empty($GLOBALS['kewl_entry_point_run'])) die('No direct access');
class sightsandsoundsassessmentprovider extends ChisimbaObject
{
    public function listActivities($contextCode) {
        return array_map(fn($a)=>['id'=>$a['id'],'name'=>$a['title'],'classification'=>$this->getObject('assignmentrules','sightsandsounds')->forActivity($contextCode,$a['id'])['mode'],'total_mark'=>100,'closing_date'=>$this->getObject('assignmentrules','sightsandsounds')->forActivity($contextCode,$a['id'])['due']],
            $this->getObject('practicebank','sightsandsounds')->activities($contextCode));
    }
    public function getActivity($contextCode,$activityId) {
        foreach($this->listActivities($contextCode) as $a)if($a['id']===$activityId)return $a;
        return false;
    }
    public function getLaunchTarget($contextCode,$activityId,$role='learner') {
        if(!$this->getActivity($contextCode,$activityId))return false;
        return $this->getObject('courseawarelaunchservice','context')->target($contextCode,'sightsandsounds',['action'=>'activity','id'=>$activityId]);
    }
    public function getStudentResult($contextCode,$activityId,$userId,$rule='latest_completed') {
        $r=$this->getObject('practicecompletions','sightsandsounds')->latest($contextCode,$activityId,$userId);
        return $r?['status'=>'marked','mark_percent'=>100*(int)$r['correct']/(int)$r['total']]:['status'=>'not_attempted','mark_percent'=>null];
    }
}
