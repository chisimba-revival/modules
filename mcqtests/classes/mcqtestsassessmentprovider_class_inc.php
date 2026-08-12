<?php
if (!$GLOBALS['kewl_entry_point_run']) { die('You cannot view this page directly'); }

/**
 * Gradebook adapter for MCQ Tests.  It exposes course activities for planning
 * but deliberately owns no assessment-plan rows and writes no MCQ data.
 */
class mcqtestsassessmentprovider extends ChisimbaObject
{
    private $tests;

    public function init()
    {
        $this->tests = $this->getObject('dbtestadmin', 'mcqtests');
    }

    public function listActivities($contextCode)
    {
        $records = $this->tests->getTests($contextCode);
        $activities = array();
        foreach ((array) $records as $record) {
            if (!empty($record['id']) && isset($record['name'])) {
                $testType = isset($record['testtype']) ? strtolower(trim($record['testtype'])) : '';
                $classification = ($testType === 'formative' || $testType === 'summative')
                    ? $testType : 'unclassified';
                $activities[] = array(
                    'id' => $record['id'],
                    'name' => $record['name'],
                    'classification' => $classification
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
}
?>
