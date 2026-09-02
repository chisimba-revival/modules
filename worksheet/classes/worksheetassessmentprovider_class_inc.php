<?php
if (!$GLOBALS['kewl_entry_point_run']) { die('You cannot view this page directly'); }

/** Read-only Gradebook discovery adapter for Worksheets. */
class worksheetassessmentprovider extends ChisimbaObject
{
    private $worksheets;
    private $results;
    private $launcher;

    /** Initialise the provider-owned activity and result stores. */
    public function init()
    {
        $this->worksheets = $this->getObject('dbworksheet', 'worksheet');
        $this->results = $this->getObject('dbworksheetresults', 'worksheet');
        $this->launcher = $this->getObject('courseawarelaunchservice', 'context');
    }

    /** Return Worksheet activities available to a Gradebook assessment plan. */
    public function listActivities($contextCode)
    {
        $filter = "context='".addslashes($contextCode)."'";
        $records = $this->worksheets->getWorksheets($filter, 'id,name,classification,activity_status,total_mark,closing_date');
        $activities = array();
        foreach ((array) $records as $record) {
            if (!empty($record['id']) && isset($record['name'])) {
                $activities[] = array(
                    'id'=>$record['id'],
                    'name'=>$record['name'],
                    'classification'=>isset($record['classification']) ? $record['classification'] : 'unclassified',
                    'activity_status'=>isset($record['activity_status']) ? $record['activity_status'] : 'inactive',
                    'total_mark'=>isset($record['total_mark']) ? (float) $record['total_mark'] : 0.0,
                    'closing_date'=>isset($record['closing_date']) ? $record['closing_date'] : null,
                );
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

    /**
     * Return a normalised learner result without exposing the legacy negative
     * unmarked sentinel as a score.
     *
     * @param string $contextCode Current course context.
     * @param string $activityId Worksheet identifier.
     * @param string $userId Learner identifier.
     * @param string $rule Gradebook result-selection rule.
     * @return array Normalised status and percentage mark.
     * @author Derek Keats
     */
    public function getStudentResult($contextCode, $activityId, $userId, $rule = 'latest_completed')
    {
        $activity = $this->getActivity($contextCode, $activityId);
        if (!is_array($activity)) {
            return array('status'=>'not_attempted', 'mark_percent'=>null);
        }
        $result = $this->results->getWorksheetResult($userId, $activityId);
        if (!is_array($result)) {
            return array('status'=>'not_attempted', 'mark_percent'=>null);
        }
        if (!isset($result['mark']) || !is_numeric($result['mark']) || (float) $result['mark'] < 0) {
            return array('status'=>'submitted', 'mark_percent'=>null);
        }
        if ((float) $activity['total_mark'] <= 0) {
            return array('status'=>'marked', 'mark_percent'=>null);
        }
        $percentage = ((float) $result['mark'] / (float) $activity['total_mark']) * 100;
        return array(
            'status'=>'marked',
            'mark_percent'=>max(0.0, min(100.0, $percentage)),
        );
    }

    /** Return the appropriate author or learner launch target. */
    public function getLaunchTarget($contextCode, $activityId, $role = 'learner')
    {
        $activity = $this->getActivity($contextCode, $activityId);
        if ($activity === false) { return false; }
        if ($role !== 'author' && $activity['activity_status'] === 'inactive') { return false; }
        return $this->launcher->target(
            $contextCode,
            'worksheet',
            array(
                'action' => $role === 'author' ? 'worksheetinfo' : 'viewworksheet',
                'id' => $activityId,
            )
        );
    }
}
?>
