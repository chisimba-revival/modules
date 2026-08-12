<?php
/**
 * Contextcontent-owned chapter-end assessment placement and progression service.
 * MCQ Tests remains the owner of test delivery, attempts and marking.
 */
if (!$GLOBALS['kewl_entry_point_run']) {
    die('You cannot view this page directly');
}

class chapterstagegateservice extends controller
{
    public function init()
    {
        $this->objContext = $this->getObject('dbcontext', 'context');
        $this->objContextChapters = $this->getObject('db_contextcontent_contextchapter', 'contextcontent');
        $this->objResults = $this->getObject('dbresults', 'mcqtests');
        $this->objTests = $this->getObject('dbtestadmin', 'mcqtests');
        $this->objUser = $this->getObject('user', 'security');
    }

    public function isGatedProgression($contextCode)
    {
        return strtolower((string) $this->objContext->getField('navigation_mode', $contextCode)) === 'gated';
    }

    public function isCourseManager($contextCode)
    {
        return $this->objUser->isAdmin()
            || $this->objUser->isContextLecturer($this->objUser->userId(), $contextCode);
    }

    public function chapterGate($contextCode, $chapterId)
    {
        $chapter = $this->objContextChapters->getChapter($chapterId);
        if ($chapter === FALSE || $chapter['contextcode'] !== $contextCode
            || empty($chapter['stage_gate_enabled']) || empty($chapter['stage_gate_testid'])) {
            return FALSE;
        }
        $test = $this->testInContext($contextCode, $chapter['stage_gate_testid']);
        if ($test === FALSE) {
            return FALSE;
        }
        return array(
            'chapterid' => $chapter['chapterid'],
            'chaptertitle' => $chapter['chaptertitle'],
            'testid' => $test['id'],
            'testname' => $test['name'],
            'passmark' => max(1, min(100, (int) $chapter['stage_gate_passmark'])),
            'totalmark' => (float) $test['totalmark']
        );
    }

    public function entryDecision($contextCode, $targetChapterId)
    {
        if (!$this->isGatedProgression($contextCode) || $this->isCourseManager($contextCode)) {
            return array('allowed' => TRUE);
        }
        $chapters = $this->objContextChapters->getContextChapters($contextCode);
        if (!is_array($chapters)) {
            return array('allowed' => TRUE);
        }
        foreach ($chapters as $chapter) {
            if ($chapter['chapterid'] === $targetChapterId) {
                break;
            }
            $gate = $this->chapterGate($contextCode, $chapter['chapterid']);
            if ($gate !== FALSE && !$this->hasPassed($gate)) {
                return array('allowed' => FALSE, 'gate' => $gate);
            }
        }
        return array('allowed' => TRUE);
    }

    public function hasPassed($gate)
    {
        return $this->bestPercentage($gate['testid'], $gate['totalmark']) >= $gate['passmark'];
    }

    public function bestPercentage($testId, $totalMark)
    {
        if ($totalMark <= 0 || !$this->objUser->isLoggedIn()) {
            return NULL;
        }
        $attempts = $this->objResults->getResult($this->objUser->userId(), $testId);
        if (!is_array($attempts)) {
            return NULL;
        }
        $best = NULL;
        foreach ($attempts as $attempt) {
            if ($attempt['endtime'] === NULL || $attempt['endtime'] === '' || (float) $attempt['mark'] < 0) {
                continue;
            }
            $percentage = ((float) $attempt['mark'] / $totalMark) * 100;
            $best = $best === NULL ? $percentage : max($best, $percentage);
        }
        return $best;
    }

    /** Return the chapter immediately following the supplied chapter, if any. */
    public function nextChapterId($contextCode, $chapterId)
    {
        $chapters = $this->objContextChapters->getContextChapters($contextCode);
        if (!is_array($chapters)) {
            return NULL;
        }
        $currentFound = FALSE;
        foreach ($chapters as $chapter) {
            if ($currentFound) {
                return $chapter['chapterid'];
            }
            if ($chapter['chapterid'] === $chapterId) {
                $currentFound = TRUE;
            }
        }
        return NULL;
    }

    private function testInContext($contextCode, $testId)
    {
        $tests = $this->objTests->getTests($contextCode, 'id,name,totalmark,context', $testId);
        if (!is_array($tests) || empty($tests[0]) || $tests[0]['context'] !== $contextCode) {
            return FALSE;
        }
        return $tests[0];
    }
}
?>