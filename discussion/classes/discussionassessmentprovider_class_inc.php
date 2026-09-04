<?php
/**
 * Gradebook and course-content adapter for Discussion activities.
 *
 * @author Derek Keats
 */
if (!$GLOBALS['kewl_entry_point_run']) { die('You cannot view this page directly'); }

class discussionassessmentprovider extends ChisimbaObject
{
    private $discussions;
    private $marks;
    private $launcher;
    private $posts;

    public function init()
    {
        $this->discussions = $this->getObject('dbdiscussion', 'discussion');
        $this->marks = $this->getObject('dbdiscussionassessmentmarks', 'discussion');
        $this->launcher = $this->getObject('courseawarelaunchservice', 'context');
        $this->posts = $this->getObject('dbpost', 'discussion');
    }

    /** Return palette-eligible discussions or the stricter marked subset. */
    public function listActivities($contextCode, $purpose = 'assessment')
    {
        $activities = array();
        foreach ((array) $this->discussions->getAllContextDiscussions($contextCode) as $record) {
            if (($record['course_activity_enabled'] ?? 'N') !== 'Y') { continue; }
            if ($purpose !== 'palette' && ($record['assessment_enabled'] ?? 'N') !== 'Y') { continue; }
            $activities[] = array(
                'id'=>(string) $record['id'],
                'name'=>(string) $record['discussion_name'],
                'classification'=>(string) ($record['assessment_classification'] ?? 'formative'),
                'total_mark'=>(float) ($record['assessment_total_mark'] ?? 100),
                'marked'=>($record['assessment_enabled'] ?? 'N') === 'Y',
            );
        }
        return $activities;
    }

    public function getActivity($contextCode, $activityId)
    {
        foreach ($this->listActivities($contextCode) as $activity) {
            if ($activity['id'] === (string) $activityId) { return $activity; }
        }
        return false;
    }

    public function getLaunchTarget($contextCode, $activityId, $role = 'learner')
    {
        $available = $this->listActivities($contextCode, 'palette');
        foreach ($available as $activity) {
            if ($activity['id'] === (string) $activityId) {
                return $this->launcher->target($contextCode, 'discussion', array(
                    'action'=>$role === 'author' ? 'markdiscussion' : 'discussion',
                    'id'=>$activityId
                ));
            }
        }
        return false;
    }

    public function getStudentResult($contextCode, $activityId, $userId, $rule = 'latest_completed')
    {
        $activity = $this->getActivity($contextCode, $activityId);
        if ($activity === false) { return array('status'=>'not_attempted', 'mark_percent'=>null); }
        $mark = $this->marks->findMark($activityId, $userId);
        if (!$mark) {
            $evidence=$this->posts->getAssessmentEvidence($activityId,$userId);
            return array('status'=>$evidence ? 'submitted' : 'not_attempted', 'mark_percent'=>null);
        }
        $percentage = ((float) $mark['mark'] / max(1, (float) $activity['total_mark'])) * 100;
        return array('status'=>'marked', 'mark_percent'=>max(0, min(100, $percentage)));
    }

    /** Count the same evidence-review queue shown in the author workspace. */
    public function getOutstandingReviewCount($contextCode, $activityId, array $studentIds)
    {
        if ($this->getActivity($contextCode, $activityId) === false) { return 0; }
        $marks = $this->marks->getForDiscussion($activityId);
        $state = $this->getObject('discussionassessmentstate', 'discussion');
        $count = 0;
        foreach ($studentIds as $studentId) {
            $evidence = $this->posts->getAssessmentEvidence($activityId, $studentId);
            $mark = isset($marks[$studentId]) ? $marks[$studentId] : null;
            if ($state->needsReview($mark, $evidence)) { $count++; }
        }
        return $count;
    }
}
