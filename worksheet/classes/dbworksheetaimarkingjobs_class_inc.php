<?php
/** Durable queue for AI-assisted worksheet marking suggestions. */
if (empty($GLOBALS['kewl_entry_point_run'])) { die('You cannot view this page directly'); }

class dbworksheetaimarkingjobs extends dbTable
{
    public function init($tableName = null, $pearDb = null, $errorCallback = 'globalPearErrorHandler')
    {
        parent::init($tableName !== null ? $tableName : 'tbl_worksheet_ai_marking_jobs', $pearDb, $errorCallback);
    }

    public function enqueue($contextCode, $userId, $resultId)
    {
        $resultId = trim((string) $resultId);
        if ($resultId === '') { return false; }
        $active = $this->getArray("SELECT id FROM tbl_worksheet_ai_marking_jobs WHERE userid='".$this->escape($userId)."' AND result_id='".$this->escape($resultId)."' AND status IN ('queued','running') ORDER BY date_created DESC LIMIT 1");
        if (is_array($active) && isset($active[0]['id'])) { return $active[0]['id']; }
        $id = bin2hex(random_bytes(16));
        $now = date('Y-m-d H:i:s');
        $this->insert(array('id'=>$id, 'contextcode'=>(string) $contextCode, 'userid'=>(string) $userId, 'result_id'=>$resultId, 'status'=>'queued', 'result_json'=>json_encode(array()), 'error_code'=>null, 'date_created'=>$now, 'date_updated'=>$now, 'date_completed'=>null));
        return $id;
    }

    public function getOwned($id, $contextCode, $userId, $isAdmin = false)
    {
        $row = $this->getRow('id', strtolower(trim((string) $id)));
        if (!is_array($row) || (string) $row['contextcode'] !== (string) $contextCode) { return false; }
        if (!$isAdmin && (string) $row['userid'] !== (string) $userId) { return false; }
        $decoded = json_decode((string) ($row['result_json'] ?? '[]'), true);
        $row['suggestions'] = is_array($decoded) ? $decoded : array();
        return $row;
    }

    /**
     * Return the newest completed AI draft retained for a worksheet submission.
     *
     * @param string $resultId Worksheet result identifier.
     * @param string $contextCode Current course context.
     * @param string $userId Lecturer who requested the draft.
     * @param bool $isAdmin Whether ownership filtering may be bypassed.
     * @return array|false Completed job with decoded suggestions, or false.
     * @author Derek Keats
     */
    public function getLatestCompletedForResult($resultId, $contextCode, $userId, $isAdmin = false)
    {
        $sql = "SELECT id FROM tbl_worksheet_ai_marking_jobs WHERE result_id='".$this->escape($resultId)."'"
            ." AND contextcode='".$this->escape($contextCode)."' AND status='completed'";
        if (!$isAdmin) { $sql .= " AND userid='".$this->escape($userId)."'"; }
        $rows = $this->getArray($sql.' ORDER BY date_completed DESC,date_created DESC LIMIT 1');
        if (!is_array($rows) || empty($rows[0]['id'])) { return false; }
        return $this->getOwned($rows[0]['id'], $contextCode, $userId, $isAdmin);
    }

    /**
     * Return the newest AI-marking job for each requested worksheet result.
     *
     * @param array $resultIds Worksheet result identifiers visible on the page.
     * @param string $contextCode Current course context.
     * @param string $userId Lecturer who created the jobs.
     * @param bool $isAdmin Whether ownership filtering may be bypassed.
     * @return array Jobs keyed by worksheet result identifier.
     */
    public function getLatestForResults($resultIds, $contextCode, $userId, $isAdmin = false)
    {
        $wanted = array_fill_keys(array_map('strval', (array) $resultIds), true);
        if (empty($wanted)) { return array(); }
        $sql = "SELECT id,result_id,status,error_code,date_updated,userid FROM tbl_worksheet_ai_marking_jobs"
            ." WHERE contextcode='".$this->escape($contextCode)."'";
        if (!$isAdmin) { $sql .= " AND userid='".$this->escape($userId)."'"; }
        $sql .= ' ORDER BY date_created DESC';
        $latest = array();
        foreach ((array) $this->getArray($sql) as $row) {
            $resultId = (string) ($row['result_id'] ?? '');
            if (isset($wanted[$resultId]) && !isset($latest[$resultId])) {
                $latest[$resultId] = $row;
            }
        }
        return $latest;
    }

    public function runOne()
    {
        $stale = date('Y-m-d H:i:s', time() - 900);
        $rows = $this->getArray("SELECT * FROM tbl_worksheet_ai_marking_jobs WHERE status='queued' OR (status='running' AND date_updated<'".$stale."') ORDER BY date_created ASC LIMIT 1");
        if (!is_array($rows) || !isset($rows[0])) { return array('selected'=>0); }
        $row = $rows[0];
        $this->update('id', $row['id'], array('status'=>'running', 'date_updated'=>date('Y-m-d H:i:s')));
        $results = $this->getObject('dbworksheetresults', 'worksheet');
        $worksheets = $this->getObject('dbworksheet', 'worksheet');
        $questionsDb = $this->getObject('dbworksheetquestions', 'worksheet');
        $answersDb = $this->getObject('dbworksheetanswers', 'worksheet');
        $result = $results->getRow('id', $row['result_id']);
        $outcome = array('ok'=>false, 'error'=>'submission_unavailable');
        if (is_array($result)) {
            $worksheet = $worksheets->getWorksheet($result['worksheet_id']);
            if (is_array($worksheet) && (string) $worksheet['context'] === (string) $row['contextcode']) {
                $questions = $questionsDb->getQuestions($result['worksheet_id']);
                $rubrics = array();
                try {
                    $modules = $this->getObject('modules', 'modulecatalogue');
                    if ($modules->checkIfRegistered('rubric')) {
                        $service = $this->getObject('rubricservice', 'rubric');
                        $defaultRubric = $this->getObject('worksheetdefaultrubric', 'worksheet')->getStructuredRubric();
                        foreach ($questions as $question) {
                            if (!empty($question['rubric_id'])) {
                                $rubric = $service->getStructuredRubric($question['rubric_id']);
                                if ($rubric !== false) { $rubrics[$question['id']] = $rubric; }
                            } elseif ($defaultRubric !== false) {
                                $rubrics[$question['id']] = $defaultRubric;
                            }
                        }
                    }
                } catch (Throwable $exception) { $rubrics = array(); }
                $answers = $answersDb->getStudentAnswers($result['worksheet_id'], $result['userid']);
                $outcome = $this->getObject('worksheetaimarker', 'worksheet')->suggest($worksheet, $questions, $answers, $rubrics);
            }
        }
        $done = date('Y-m-d H:i:s');
        $this->update('id', $row['id'], array('status'=>!empty($outcome['ok']) ? 'completed' : 'failed', 'result_json'=>json_encode($outcome['suggestions'] ?? array()), 'error_code'=>!empty($outcome['ok']) ? null : (string) ($outcome['error'] ?? 'provider_failed'), 'date_updated'=>$done, 'date_completed'=>$done));
        return array('selected'=>1, 'completed'=>1, 'jobId'=>$row['id']);
    }

    /** Remove AI suggestions when a reopened submission makes the draft stale. */
    public function deleteForResult($resultId)
    {
        $rows = $this->getArray("SELECT id FROM tbl_worksheet_ai_marking_jobs WHERE result_id='".$this->escape($resultId)."'");
        foreach ((array) $rows as $row) {
            if (!empty($row['id'])) { $this->delete('id', $row['id']); }
        }
    }

    private function escape($value) { return str_replace("'", "''", (string) $value); }
}
?>
