<?php
/** AI-assisted, lecturer-reviewed marking for Online Worksheets. */
if (empty($GLOBALS['kewl_entry_point_run'])) { die('You cannot view this page directly'); }

class worksheetaimarker extends ChisimbaObject
{
    private $aiService = null;

    public function isAvailable()
    {
        return $this->ensureAiAvailable();
    }

    /**
     * Generate editable suggestions. This method never writes marks to the database.
     */
    public function suggest(array $worksheet, array $questions, array $answers, array $rubrics = array())
    {
        if (!$this->ensureAiAvailable()) {
            return array('ok' => false, 'error' => 'ai_unavailable');
        }

        $answerMap = array();
        foreach ($answers as $answer) {
            if (!empty($answer['question_id']) && !empty($answer['id'])) {
                $answerMap[$answer['question_id']] = $answer;
            }
        }

        $items = array();
        foreach ($questions as $question) {
            if (empty($question['id']) || empty($answerMap[$question['id']])) { continue; }
            $answer = $answerMap[$question['id']];
            $item = array(
                'answerId' => $answer['id'],
                'question' => strip_tags((string) ($question['question'] ?? '')),
                'modelAnswer' => strip_tags((string) ($question['model_answer'] ?? '')),
                'studentAnswer' => strip_tags((string) ($answer['answer'] ?? '')),
                'maximumMark' => max(0, (int) ($question['question_worth'] ?? 0)),
            );
            if (!empty($rubrics[$question['id']])) {
                $item['rubric'] = $rubrics[$question['id']];
            }
            $items[] = $item;
        }
        if (empty($items)) { return array('ok' => false, 'error' => 'no_answers'); }

        $schema = array(
            'type' => 'object',
            'properties' => array(
                'suggestions' => array(
                    'type' => 'array',
                    'items' => array(
                        'type' => 'object',
                        'properties' => array(
                            'answerId' => array('type' => 'string'),
                            'mark' => array('type' => 'integer', 'minimum' => 0),
                            'feedback' => array('type' => 'string'),
                        ),
                        'required' => array('answerId', 'mark', 'feedback'),
                        'additionalProperties' => false,
                    ),
                ),
            ),
            'required' => array('suggestions'),
            'additionalProperties' => false,
        );

        $result = $this->aiService->execute(array(
            'consumer' => 'worksheet',
            'task' => 'suggest_worksheet_marks',
            'instructions' => 'Suggest a mark and concise constructive feedback for every supplied answer. Use only the question, model answer, maximum mark, and rubric supplied. Apply every rubric criterion when one is present. Evaluate coverage of the essential knowledge in the model answer as well as correctness, reasoning, relevance and clarity. Reserve 90-100 percent for comprehensive answers with only trivial omissions; use 75-89 percent for strong answers with limited omissions; 50-74 percent for partial answers with substantial omissions; 25-49 percent for limited answers; and 0-24 percent for absent, irrelevant or fundamentally incorrect answers. Never exceed maximumMark. These are suggestions for a human lecturer, so do not claim the result is final.',
            'input' => json_encode(array('worksheet' => (string) ($worksheet['name'] ?? ''), 'answers' => $items)),
            'schemaName' => 'worksheet_marking_suggestions',
            'schema' => $schema,
        ));
        if (empty($result['ok']) || !isset($result['data']['suggestions'])) {
            return array('ok' => false, 'error' => (string) ($result['error'] ?? 'provider_failed'));
        }

        $limits = array();
        foreach ($items as $item) { $limits[$item['answerId']] = $item['maximumMark']; }
        $suggestions = array();
        foreach ((array) $result['data']['suggestions'] as $suggestion) {
            $answerId = (string) ($suggestion['answerId'] ?? '');
            if ($answerId === '' || !isset($limits[$answerId])) { continue; }
            $suggestions[$answerId] = array(
                'mark' => min($limits[$answerId], max(0, (int) ($suggestion['mark'] ?? 0))),
                'feedback' => trim((string) ($suggestion['feedback'] ?? '')),
            );
        }
        return count($suggestions) === count($items)
            ? array('ok' => true, 'suggestions' => $suggestions)
            : array('ok' => false, 'error' => 'incomplete_suggestions');
    }

    private function ensureAiAvailable()
    {
        if ($this->aiService !== null) {
            return method_exists($this->aiService, 'isAvailable') && $this->aiService->isAvailable();
        }
        try {
            $modules = $this->getObject('modules', 'modulecatalogue');
            if (!$modules->checkIfRegistered('ai')) { return false; }
            $this->aiService = $this->getObject('aiservice', 'ai');
            return method_exists($this->aiService, 'isAvailable') && $this->aiService->isAvailable();
        } catch (Throwable $exception) {
            $this->aiService = null;
            return false;
        }
    }
}
?>
