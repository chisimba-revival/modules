<?php
/** Durable queue for AI chapter-quiz preview generation. */
if (empty($GLOBALS['kewl_entry_point_run'])) { die('You cannot view this page directly'); }

class dbchapterquizjobs extends dbTable
{
    private $generator;

    public function init($tableName = null, $pearDb = null, $errorCallback = 'globalPearErrorHandler')
    {
        parent::init($tableName !== null ? $tableName : 'tbl_mcq_chapter_quiz_jobs', $pearDb, $errorCallback);
        $this->generator = $this->getObject('chapterquizgenerator', 'mcqtests');
    }

    public function enqueue($contextCode, $userId, array $chapterIds)
    {
        $wanted = array_fill_keys(array_map('strval', $chapterIds), true);
        $eligible = array();
        foreach ($this->generator->chapterCandidates($contextCode) as $chapter) {
            if (!empty($wanted[$chapter['chapterId']]) && !empty($chapter['eligible'])) {
                $eligible[] = (string) $chapter['chapterId'];
            }
        }
        if (empty($eligible)) { return false; }
        $active = $this->getArray(
            "SELECT id FROM tbl_mcq_chapter_quiz_jobs WHERE userid = '" . $this->escape($userId)
            . "' AND contextcode = '" . $this->escape($contextCode)
            . "' AND status IN ('queued','running') ORDER BY date_created DESC LIMIT 1"
        );
        if (is_array($active) && isset($active[0]['id'])) { return $active[0]['id']; }
        $id = bin2hex(random_bytes(16));
        $clock = $this->getObject('timeanddateservice', 'timeanddate-service');
        $now = $clock->nowStorage();
        $this->insert(array(
            'id' => $id, 'contextcode' => (string) $contextCode, 'userid' => (string) $userId,
            'status' => 'queued', 'chapter_ids' => json_encode($eligible),
            'result_json' => json_encode(array()), 'error_json' => json_encode(array()),
            'progress_total' => count($eligible), 'progress_completed' => 0,
            'current_chapter' => null, 'date_created' => $now, 'date_updated' => $now,
            'date_completed' => null,
        ));
        return $id;
    }

    public function getOwned($id, $contextCode, $userId, $isAdmin = false)
    {
        $row = $this->getRow('id', strtolower(trim((string) $id)));
        if (!is_array($row) || (string) $row['contextcode'] !== (string) $contextCode) { return false; }
        if (!$isAdmin && (string) $row['userid'] !== (string) $userId) { return false; }
        return $this->normalise($row);
    }

    /** Process one chapter, keeping every completed preview durable. */
    public function runOne()
    {
        $clock = $this->getObject('timeanddateservice', 'timeanddate-service');
        $stale = $clock->toStorage($clock->nowUtc()->modify('-15 minutes'));
        $rows = $this->getArray(
            "SELECT * FROM tbl_mcq_chapter_quiz_jobs WHERE status = 'queued'"
            . " OR (status = 'running' AND date_updated < '" . $stale . "')"
            . " ORDER BY date_created ASC LIMIT 1"
        );
        if (!is_array($rows) || !isset($rows[0])) { return array('selected' => 0); }
        $row = $this->normalise($rows[0]);
        $ids = $row['chapterIds'];
        $position = (int) $row['progress_completed'];
        if (!isset($ids[$position])) { return $this->finish($row); }

        $chapterId = (string) $ids[$position];
        $title = $chapterId;
        foreach ($this->generator->chapterCandidates($row['contextcode']) as $candidate) {
            if ((string) $candidate['chapterId'] === $chapterId) { $title = $candidate['title']; break; }
        }
        $now = $clock->nowStorage();
        $this->update('id', $row['id'], array('status' => 'running', 'current_chapter' => $title, 'date_updated' => $now));
        $outcome = $this->generator->generate($row['contextcode'], array($chapterId));
        $results = $row['results'];
        $errors = $row['errors'];
        foreach ($outcome['chapters'] ?? array() as $chapter) { $results[] = $chapter; }
        foreach ($outcome['errors'] ?? array() as $error) { $errors[] = $error; }
        if (empty($outcome['chapters']) && empty($outcome['errors'])) {
            $errors[] = array('title' => $title, 'error' => 'chapter_no_longer_eligible');
        }
        $position++;
        $done = $position >= count($ids);
        $this->update('id', $row['id'], array(
            'status' => $done ? (empty($results) ? 'failed' : 'completed') : 'queued',
            'result_json' => json_encode($results), 'error_json' => json_encode($errors),
            'progress_completed' => $position, 'current_chapter' => null,
            'date_updated' => $clock->nowStorage(),
            'date_completed' => $done ? $clock->nowStorage() : null,
        ));
        return array('selected' => 1, 'jobId' => $row['id'], 'completed' => $done ? 1 : 0);
    }

    private function finish(array $row)
    {
        $status = empty($row['results']) ? 'failed' : 'completed';
        $now = $this->getObject('timeanddateservice', 'timeanddate-service')->nowStorage();
        $this->update('id', $row['id'], array('status' => $status, 'date_updated' => $now, 'date_completed' => $now));
        return array('selected' => 1, 'jobId' => $row['id'], 'completed' => 1);
    }

    private function normalise(array $row)
    {
        $row['chapterIds'] = $this->decode($row['chapter_ids'] ?? '[]');
        $row['results'] = $this->decode($row['result_json'] ?? '[]');
        $row['errors'] = $this->decode($row['error_json'] ?? '[]');
        return $row;
    }

    private function decode($json)
    {
        $value = json_decode((string) $json, true);
        return is_array($value) ? $value : array();
    }

    private function escape($value)
    {
        return str_replace("'", "''", (string) $value);
    }
}
?>
