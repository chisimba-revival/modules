<?php
if (!$GLOBALS['kewl_entry_point_run']) { die('You cannot view this page directly'); }

/**
 * Read-only Gradebook adapter for Assignment activities.
 * Assignment owns activity and submission records; Gradebook owns weighting.
 */
class assignmentassessmentprovider extends ChisimbaObject
{
    private $assignments;

    public function init()
    {
        $this->assignments = $this->getObject('dbassignment', 'assignment');
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
                'classification' => $classification
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
}
?>
