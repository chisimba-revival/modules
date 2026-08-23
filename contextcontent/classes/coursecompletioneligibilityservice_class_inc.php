<?php
/** One course-completion decision shared by certificates and future consumers. */
if (empty($GLOBALS['kewl_entry_point_run'])) { die('You cannot view this page directly'); }
class coursecompletioneligibilityservice extends ChisimbaObject
{
    public function init()
    {
        $this->user=$this->getObject('user','security');
        $this->order=$this->getObject('db_contextcontent_order','contextcontent');
        $this->activity=$this->getObject('db_contextcontent_activitystreamer','contextcontent');
        $this->gates=$this->getObject('chapterstagegateservice','contextcontent');
        $this->plans=$this->getObject('dbgradebookassessmentplans','gradebook');
        $this->items=$this->getObject('dbgradebookassessmentplanitems','gradebook');
        $this->providers=$this->getObject('assessmentproviderregistry','gradebook');
    }
    public function evaluate($contextCode,$userId=null)
    {
        $userId=$userId?:$this->user->userId();$total=(int)$this->order->getNumContextPages($contextCode);
        $visited=(int)$this->activity->countVisitedPages($userId,$contextCode);$reasons=array();
        if($total<1||$visited<$total){$reasons[]='content_incomplete';}
        $gateSummary=$this->gates->courseCompletionSummary($contextCode);
        if($this->gates->isGatedProgression($contextCode)&&empty($gateSummary['complete'])){$reasons[]='stage_gates_incomplete';}
        $assessments=array();$plan=$this->plans->findForContext($contextCode);
        if($plan){foreach($this->items->getForPlan($plan['id']) as $item){if(($item['status']??'active')!=='active'){continue;}$adapter=$this->providers->adapter($item['provider_key']);$result=is_object($adapter)&&is_callable(array($adapter,'getStudentResult'))?$adapter->getStudentResult($contextCode,$item['activity_id'],$userId,$item['result_rule']):array('status'=>'unavailable','mark_percent'=>null);$passed=$result['status']==='marked'&&$result['mark_percent']!==null&&(float)$result['mark_percent']>=50;$assessments[]=array('name'=>$item['name'],'passed'=>$passed,'mark_percent'=>$result['mark_percent']);if(!$passed){$reasons[]='assessment_not_passed';}}}
        return array('eligible'=>empty($reasons),'reasons'=>array_values(array_unique($reasons)),'content'=>array('visited'=>$visited,'total'=>$total),'stage_gates'=>$gateSummary,'assessments'=>$assessments,'completed_at'=>date('Y-m-d H:i:s'),'reference'=>'course:'.$contextCode.':'.$userId.':v1');
    }
}
?>
