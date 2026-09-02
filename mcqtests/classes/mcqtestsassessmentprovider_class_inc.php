<?php
if (!$GLOBALS['kewl_entry_point_run']) { die('You cannot view this page directly'); }

/**
 * Gradebook adapter for MCQ Tests.  It exposes course activities for planning
 * but deliberately owns no assessment-plan rows and writes no MCQ data.
 */
class mcqtestsassessmentprovider extends ChisimbaObject
{
    private $tests;
    private $results;
    private $launcher;

    public function init()
    {
        $this->tests = $this->getObject('dbtestadmin', 'mcqtests');
        $this->results = $this->getObject('dbresults', 'mcqtests');
        $this->launcher = $this->getObject('courseawarelaunchservice', 'context');
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
                    'classification' => $classification,
                    'total_mark' => isset($record['totalmark']) ? (float) $record['totalmark'] : 0.0,
                    'closing_date' => isset($record['closingdate']) ? $record['closingdate'] : null
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

    /** Open test management for authors or the attempt journey for learners. */
    public function getLaunchTarget($contextCode, $activityId, $role = 'learner')
    {
        if ($this->getActivity($contextCode, $activityId) === false) { return false; }
        return $this->launcher->target(
            $contextCode,
            'mcqtests',
            array(
                'action'=>$role === 'author' ? 'view' : 'answertest',
                'id'=>$activityId,
            )
        );
    }

    /** Return a normalised learner result without exposing legacy sentinels. */
    public function getStudentResult($contextCode, $activityId, $userId, $rule = 'latest_completed')
    {
        $activity = $this->getActivity($contextCode, $activityId);
        if (!is_array($activity) || $activity['total_mark'] <= 0) {
            return array('status' => 'not_attempted', 'mark_percent' => null);
        }
        $attempts = $this->results->getResult($userId, $activityId);
        if (empty($attempts)) {
            return array('status' => 'not_attempted', 'mark_percent' => null);
        }
        foreach ((array) $attempts as $attempt) {
            if (array_key_exists('mark', $attempt) && is_numeric($attempt['mark'])
                && (float) $attempt['mark'] >= 0) {
                $percentage = ((float) $attempt['mark'] / $activity['total_mark']) * 100;
                return array(
                    'status' => 'marked',
                    'mark_percent' => max(0.0, min(100.0, $percentage))
                );
            }
        }
        return array('status' => 'in_progress', 'mark_percent' => null);
    }
}
?>
