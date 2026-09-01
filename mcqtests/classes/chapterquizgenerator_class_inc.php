<?php
/** Generate complete grounded quizzes directly from course chapters. */
if (empty($GLOBALS['kewl_entry_point_run'])) { die('You cannot view this page directly'); }

class chapterquizgenerator extends ChisimbaObject
{
    private $chapters;
    private $order;
    private $tests;
    private $ai;

    public function init()
    {
        $this->chapters = $this->getObject('db_contextcontent_contextchapter', 'contextcontent');
        $this->order = $this->getObject('db_contextcontent_order', 'contextcontent');
        $this->tests = $this->getObject('dbtestadmin', 'mcqtests');
        $this->ai = $this->getObject('mcqaigenerator', 'mcqtests');
    }

    public function chapterCandidates($contextCode)
    {
        $rows = $this->chapters->getContextChapters($contextCode);
        $result = array();
        foreach (is_array($rows) ? $rows : array() as $row) {
            $source = $this->chapterSource($contextCode, $row);
            $existingTestId = trim((string) ($row['stage_gate_testid'] ?? ''));
            $result[] = array(
                'chapterId' => (string) $row['chapterid'],
                'contextChapterId' => (string) $row['contextchapterid'],
                'title' => trim((string) $row['chaptertitle']),
                'source' => $source,
                'sourceLength' => mb_strlen($source, 'UTF-8'),
                'eligible' => mb_strlen($source, 'UTF-8') >= 100 && $existingTestId === '',
                'existingTestId' => $existingTestId,
            );
        }
        return $result;
    }

    public function generate($contextCode, array $chapterIds)
    {
        $selected = array_fill_keys(array_map('strval', $chapterIds), true);
        $generated = array();
        $errors = array();
        foreach ($this->chapterCandidates($contextCode) as $chapter) {
            if (empty($selected[$chapter['chapterId']]) || !$chapter['eligible']) { continue; }
            $result = $this->ai->generate($chapter['source']);
            if (empty($result['ok'])) {
                $errors[] = array('title' => $chapter['title'], 'error' => $result['error'] ?? 'provider_failed');
                continue;
            }
            unset($chapter['source']);
            $chapter['questions'] = $result['questions'];
            $generated[] = $chapter;
        }
        return array('ok' => !empty($generated), 'chapters' => $generated, 'errors' => $errors);
    }

    public function insert($contextCode, $userId, array $generated, $passMark = 70)
    {
        if ($passMark < 1 || $passMark > 100 || empty($generated)) {
            return array('ok' => false, 'error' => 'invalid_candidates');
        }
        $created = array();
        $this->tests->beginTransaction();
        try {
            foreach ($generated as $chapter) {
                $current = $this->chapters->getChapter($chapter['chapterId'], $contextCode);
                if (!is_array($current)
                    || (string) $current['contextchapterid'] !== (string) $chapter['contextChapterId']
                    || trim((string) ($current['stage_gate_testid'] ?? '')) !== '') {
                    throw new RuntimeException('chapter_changed');
                }
                $testId = $this->tests->addTest(array(
                    'context' => $contextCode,
                    'chapter' => (string) $chapter['chapterId'],
                    'userid' => (string) $userId,
                    'name' => mb_substr(trim((string) $chapter['title']), 0, 60, 'UTF-8'),
                    'description' => 'Chapter quiz for ' . trim((string) $chapter['title']),
                    'status' => 'inactive', 'totalmark' => 0, 'percentage' => 0,
                    'duration' => 0, 'timed' => 0, 'testtype' => 'Formative',
                    'qsequence' => 'Sequential', 'asequence' => 'Scrambled',
                    'comlab' => '', 'updated' => $this->getObject('timeanddateservice', 'timeanddate-service')->nowStorage(),
                    'coursePermissions' => 'Private'
                ));
                if (empty($testId)) { throw new RuntimeException('test_insert_failed'); }
                $questionResult = $this->ai->insertQuestions($testId, $chapter['questions']);
                if (empty($questionResult['ok'])) {
                    throw new RuntimeException($questionResult['error'] ?? 'question_insert_failed');
                }
                if ($this->chapters->updateChapterStageGate(
                    $chapter['contextChapterId'], $testId, $passMark, 1
                ) === false) { throw new RuntimeException('chapter_link_failed'); }
                $created[] = array('testId' => $testId, 'title' => $chapter['title']);
            }
            $this->tests->commitTransaction();
            return array('ok' => true, 'created' => $created);
        } catch (Throwable $error) {
            $this->tests->rollbackTransaction();
            return array('ok' => false, 'error' => $error->getMessage());
        }
    }

    private function chapterSource($contextCode, array $chapter)
    {
        $parts = array($chapter['chaptertitle'] ?? '', $chapter['introduction'] ?? '');
        $pages = $this->order->getPages($chapter['chapterid'], $contextCode);
        foreach (is_array($pages) ? $pages : array() as $page) {
            $full = $this->order->getPage($page['id'], $contextCode);
            if (is_array($full)) {
                $parts[] = $full['menutitle'] ?? '';
                $parts[] = $full['pagecontent'] ?? '';
            }
        }
        $text = html_entity_decode(strip_tags(implode("\n\n", $parts)), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        return trim(preg_replace('/\s+/u', ' ', $text));
    }
}
?>
