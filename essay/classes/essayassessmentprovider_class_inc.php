<?php
if (!$GLOBALS['kewl_entry_point_run']) { die('You cannot view this page directly'); }

/** Read-only Gradebook discovery adapter for Essay topics. */
class essayassessmentprovider extends ChisimbaObject
{
    private $topics;
    public function init() { $this->topics = $this->getObject('dbessay_topics', 'essay'); }
    public function listActivities($contextCode)
    {
        $filter = "context='".addslashes($contextCode)."'";
        $records = $this->topics->getTopic(null, 'id,name', $filter);
        $activities = array();
        foreach ((array) $records as $record) {
            if (!empty($record['id']) && isset($record['name'])) {
                $activities[] = array('id'=>$record['id'], 'name'=>$record['name'], 'classification'=>'manual_marking');
            }
        }
        return $activities;
    }
    public function getActivity($contextCode, $activityId)
    {
        foreach ($this->listActivities($contextCode) as $activity) {
            if ((string) $activity['id'] === (string) $activityId) { return $activity; }
        }
        return false;
    }
}
?>
