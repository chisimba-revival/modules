<?php
if (!$GLOBALS['kewl_entry_point_run']) { die('You cannot view this page directly'); }

/** Read-only Gradebook discovery adapter for Essay topics. */
class essayassessmentprovider extends ChisimbaObject
{
    private $topics;
    private $bookings;
    private $user;
    private $launcher;
    public function init()
    {
        $this->topics = $this->getObject('dbessay_topics', 'essay');
        $this->bookings = $this->getObject('dbessay_book', 'essay');
        $this->user = $this->getObject('user', 'security');
        $this->launcher = $this->getObject('courseawarelaunchservice', 'context');
    }
    public function listActivities($contextCode)
    {
        $filter = "context='".addslashes($contextCode)."'";
        $records = $this->topics->getTopic(null, 'id,name,closing_date,bypass', $filter);
        $activities = array();
        foreach ((array) $records as $record) {
            if (!empty($record['id']) && isset($record['name'])) {
                $activities[] = array(
                    'id'=>$record['id'],
                    'name'=>$record['name'],
                    'classification'=>'formative',
                    'closing_date'=>isset($record['closing_date']) ? $record['closing_date'] : null,
                    'bypass'=>!empty($record['bypass']),
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
    /** Open the selected essay topic using the module's role-aware view. */
    public function getLaunchTarget($contextCode, $activityId, $role = 'learner')
    {
        if ($this->getActivity($contextCode, $activityId) === false) { return false; }
        $essayAction = 'view';
        if ($role !== 'author') {
            $result = $this->getStudentResult(
                $contextCode,
                $activityId,
                $this->user->userId()
            );
            if (($result['status'] ?? 'not_attempted') !== 'not_attempted') {
                $essayAction = 'viewallessays';
            }
        }
        return $this->launcher->target(
            $contextCode,
            'essay',
            array(
                'action'=>$essayAction,
                'id'=>$essayAction === 'view' ? $activityId : '',
            )
        );
    }

    /**
     * Return the learner's current result for one Essay topic.
     *
     * The booking table is the Essay module's source of truth: booking means
     * started, a submission timestamp means submitted, and a numeric mark means
     * marked. The timestamp covers both in-Chisimba writing and file uploads.
     *
     * @author Derek Keats
     * @return array{status:string,mark_percent:?float}
     */
    public function getStudentResult($contextCode, $activityId, $studentId, $rule = 'latest_completed')
    {
        if ($this->getActivity($contextCode, $activityId) === false) {
            return array('status'=>'not_attempted', 'mark_percent'=>null);
        }
        $filter = "WHERE context='".addslashes($contextCode)."'"
            ." AND topicid='".addslashes($activityId)."'"
            ." AND studentid='".addslashes($studentId)."'"
            .' ORDER BY updated DESC';
        $rows = $this->bookings->getBooking($filter);
        if (empty($rows)) {
            return array('status'=>'not_attempted', 'mark_percent'=>null);
        }
        $row = $rows[0];
        if ($row['mark'] !== null && $row['mark'] !== '') {
            return array('status'=>'marked', 'mark_percent'=>(float) $row['mark']);
        }
        if (!empty($row['submitdate'])) {
            return array('status'=>'submitted', 'mark_percent'=>null);
        }
        return array('status'=>'in_progress', 'mark_percent'=>null);
    }
}
?>
