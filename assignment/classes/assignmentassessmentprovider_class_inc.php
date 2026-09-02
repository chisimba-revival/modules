<?php
if (!$GLOBALS['kewl_entry_point_run']) { die('You cannot view this page directly'); }

/**
 * Read-only Gradebook adapter for Assignment activities.
 * Assignment owns activity and submission records; Gradebook owns weighting.
 */
class assignmentassessmentprovider extends ChisimbaObject
{
    private $assignments;
    private $submissions;
    private $launcher;

    public function init()
    {
        $this->assignments = $this->getObject('dbassignment', 'assignment');
        $this->submissions = $this->getObject('dbassignmentsubmit', 'assignment');
        $this->launcher = $this->getObject('courseawarelaunchservice', 'context');
    }

    public function listActivities($contextCode)
    {
        $activities = array();
        foreach ((array) $this->assignments->getAssignments($contextCode) as $record) {
            if (empty($record['id']) || !isset($record['name'])) {
                continue;
            }
            $classification = isset($record['assessment_classification'])
                ? strtolower(trim((string) $record['assessment_classification'])) : 'summative';
            if (!in_array($classification, array('formative', 'summative'), true)) {
                $classification = 'summative';
            }
            $activities[] = array(
                'id' => $record['id'],
                'name' => $record['name'],
                'classification' => $classification,
                'total_mark' => isset($record['mark']) ? (float) $record['mark'] : 100.0,
                'closing_date' => isset($record['closing_date']) ? $record['closing_date'] : null
            );
        }
        return $activities;
    }

    public function getActivity($contextCode, $activityId)
    {
        foreach ($this->listActivities($contextCode) as $activity) {
            if ((string) $activity['id'] === (string) $activityId) {
                return $activity;
            }
        }
        return false;
    }

    /** Open the same assignment journey used by the module for either role. */
    public function getLaunchTarget($contextCode, $activityId, $role = 'learner')
    {
        if ($this->getActivity($contextCode, $activityId) === false) { return false; }
        return $this->launcher->target(
            $contextCode,
            'assignment',
            array('action'=>'view', 'id'=>$activityId)
        );
    }

    /** Return the newest submission, distinguishing submitted from marked. */
    public function getStudentResult($contextCode, $activityId, $userId, $rule = 'latest_completed')
    {
        $activity = $this->getActivity($contextCode, $activityId);
        if ($activity === false) {
            return array('status' => 'not_attempted', 'mark_percent' => null);
        }
        $submissions = $this->submissions->getStudentAssignment($userId, $activityId);
        if (empty($submissions)) {
            return array('status' => 'not_attempted', 'mark_percent' => null);
        }
        $submission = $submissions[0];
        if (!array_key_exists('mark', $submission) || $submission['mark'] === null
            || $submission['mark'] === '' || !is_numeric($submission['mark'])
            || (float) $submission['mark'] < 0) {
            return array('status' => 'submitted', 'mark_percent' => null);
        }
        $total = isset($activity['total_mark']) && (float) $activity['total_mark'] > 0
            ? (float) $activity['total_mark'] : 100.0;
        $percentage = ((float) $submission['mark'] / $total) * 100;
        return array(
            'status' => 'marked',
            'mark_percent' => max(0.0, min(100.0, $percentage))
        );
    }
}
?>
