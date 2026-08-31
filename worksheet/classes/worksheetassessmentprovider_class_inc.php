<?php
if (!$GLOBALS['kewl_entry_point_run']) { die('You cannot view this page directly'); }

/** Read-only Gradebook discovery adapter for Worksheets. */
class worksheetassessmentprovider extends ChisimbaObject
{
    private $worksheets;
    public function init() { $this->worksheets = $this->getObject('dbworksheet', 'worksheet'); }
    public function listActivities($contextCode)
    {
        $filter = "context='".addslashes($contextCode)."'";
        $records = $this->worksheets->getWorksheets($filter, 'id,name,classification,activity_status');
        $activities = array();
        foreach ((array) $records as $record) {
            if (!empty($record['id']) && isset($record['name'])) {
                $activities[] = array('id'=>$record['id'], 'name'=>$record['name'], 'classification'=>isset($record['classification']) ? $record['classification'] : 'unclassified', 'activity_status'=>isset($record['activity_status']) ? $record['activity_status'] : 'inactive');
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
    public function getLaunchTarget($contextCode, $activityId, $role = 'learner')
    {
        $activity = $this->getActivity($contextCode, $activityId);
        if ($activity === false) { return false; }
        if ($role !== 'author' && $activity['activity_status'] === 'inactive') { return false; }
        return array(
            'module' => 'worksheet',
            'params' => array(
                'action' => $role === 'author' ? 'worksheetinfo' : 'viewworksheet',
                'id' => $activityId,
            ),
        );
    }
}
?>
