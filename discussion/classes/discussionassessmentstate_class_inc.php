<?php
/** Evidence identity shared by human reviews and AI drafts. @author Derek Keats */
if (empty($GLOBALS['kewl_entry_point_run'])) { die('You cannot view this page directly'); }
class discussionassessmentstate extends ChisimbaObject
{
    public function fingerprint(array $evidence)
    {
        // Sort independently of query order; content changes matter even within one second.
        $items = array();
        foreach ($evidence as $post) {
            $items[] = array((string)$post['postId'], (string)$post['topicId'],
                (string)($post['date'] ?? ''), (string)($post['title'] ?? ''),
                (string)($post['text'] ?? ''), (string)($post['revision'] ?? ''));
        }
        sort($items);
        return hash('sha256', serialize($items));
    }

    public function jobMatches($job, array $evidence)
    {
        if (!is_array($job)) { return false; }
        $snapshot = json_decode((string)($job['evidence_json'] ?? ''), true);
        return is_array($snapshot) && hash_equals($this->fingerprint($snapshot), $this->fingerprint($evidence));
    }

    public function needsReview($mark, array $evidence)
    {
        if (!$mark && !$evidence) { return false; }
        // Legacy marks have no snapshot: retain their scores, but require one explicit review.
        return !is_array($mark) || !hash_equals((string)($mark['evidence_fingerprint'] ?? ''), $this->fingerprint($evidence));
    }
}
