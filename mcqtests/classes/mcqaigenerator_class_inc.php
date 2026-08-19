<?php
/**
 * Domain-specific AI consumer for MCQ question generation.
 *
 * The shared AI module owns provider execution. This class owns the MCQ task
 * instructions, response schema, grounding validation and insertion through
 * the canonical MCQ question and answer table owners.
 *
 * @category  Chisimba
 * @package   mcqtests
 * @author    Derek Keats
 * @copyright 2026 Derek Keats
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License
 */
if (empty($GLOBALS['kewl_entry_point_run'])) { die('You cannot view this page directly'); }

class mcqaigenerator extends ChisimbaObject
{
    private $aiService = null;
    private $dbQuestions;
    private $dbAnswers;
    private $dbTestadmin;

    public function init()
    {
        $this->dbQuestions = $this->getObject('dbquestions', 'mcqtests');
        $this->dbAnswers = $this->getObject('dbanswers', 'mcqtests');
        $this->dbTestadmin = $this->getObject('dbtestadmin', 'mcqtests');
    }

    public function generate($sourceText)
    {
        $sourceText = trim((string) $sourceText);
        if (mb_strlen($sourceText, 'UTF-8') < 100) {
            return array('ok' => false, 'error' => 'source_too_short');
        }

        if ($this->aiService === null) {
            $this->aiService = $this->getObject('aiservice', 'ai');
        }

        $schema = array(
            'type' => 'object',
            'properties' => array(
                'questions' => array(
                    'type' => 'array',
                    'minItems' => 5,
                    'maxItems' => 5,
                    'items' => array(
                        'type' => 'object',
                        'properties' => array(
                            'stem' => array('type' => 'string'),
                            'options' => array(
                                'type' => 'array',
                                'minItems' => 4,
                                'maxItems' => 4,
                                'items' => array('type' => 'string')
                            ),
                            'correctIndex' => array(
                                'type' => 'integer',
                                'minimum' => 0,
                                'maximum' => 3
                            ),
                            'sourceBasis' => array('type' => 'string')
                        ),
                        'required' => array('stem', 'options', 'correctIndex', 'sourceBasis'),
                        'additionalProperties' => false
                    )
                )
            ),
            'required' => array('questions'),
            'additionalProperties' => false
        );

        $instructions =
            "Create exactly five single-answer multiple-choice questions using ONLY the supplied source text. "
            . "Do not use, infer, introduce, test, or rely on any fact or knowledge that is not explicitly present in the source. "
            . "Each question must have exactly four distinct answer options and exactly one correct option. "
            . "Distractors must be plausible in the context of the source but must not introduce external factual claims. "
            . "Questions must assess meaningful understanding rather than trivial wording. "
            . "For every question, sourceBasis must be a short VERBATIM excerpt copied from the supplied source that directly supports the correct answer. "
            . "Do not paraphrase sourceBasis. If the source cannot support five unambiguous questions under these rules, do not invent material.";

        $result = $this->aiService->execute(array(
            'consumer' => 'mcqtests',
            'task' => 'generate_grounded_mcq_questions',
            'instructions' => $instructions,
            'input' => $sourceText,
            'schemaName' => 'mcqtests_grounded_five_questions',
            'schema' => $schema
        ));

        if (empty($result['ok']) || empty($result['data']['questions'])) {
            return array(
                'ok' => false,
                'error' => isset($result['error']) ? (string) $result['error'] : 'provider_failed'
            );
        }

        $questions = $this->validateQuestions($sourceText, $result['data']['questions']);
        if ($questions === false) {
            return array('ok' => false, 'error' => 'grounding_validation_failed');
        }

        return array('ok' => true, 'questions' => $questions);
    }

    public function insertQuestions($testId, array $questions)
    {
        $testId = trim((string) $testId);
        if ($testId === '' || count($questions) !== 5) {
            return array('ok' => false, 'error' => 'invalid_candidates');
        }

        $test = $this->dbTestadmin->getRow('id', $testId);
        if (!is_array($test) || empty($test['id'])) {
            return array('ok' => false, 'error' => 'invalid_test');
        }

        $order = (int) $this->dbQuestions->getMaxOrder($testId);
        $inserted = 0;
        foreach ($questions as $question) {
            if (!$this->validCandidate($question)) {
                return array('ok' => false, 'error' => 'invalid_candidates');
            }

            $order++;
            $stem = trim((string) $question['stem']);
            $questionId = $this->dbQuestions->addQuestion(array(
                'testid' => $testId,
                'question' => $stem,
                'questiontext' => mb_substr(strip_tags($stem), 0, 255, 'UTF-8'),
                'hint' => '',
                'mark' => 1,
                'questionorder' => $order,
                'questiontype' => 'mcq',
                'qtype' => 'mcq'
            ));

            if (empty($questionId)) {
                return array('ok' => false, 'error' => 'question_insert_failed');
            }

            foreach ($question['options'] as $index => $answer) {
                $answerId = $this->dbAnswers->addAnswers(array(
                    'testid' => $testId,
                    'questionid' => $questionId,
                    'answer' => trim((string) $answer),
                    'commenttext' => '',
                    'answerorder' => $index + 1,
                    'correct' => ((int) $question['correctIndex'] === $index) ? 1 : 0
                ));
                if (empty($answerId)) {
                    return array('ok' => false, 'error' => 'answer_insert_failed');
                }
            }
            $inserted++;
        }

        $this->dbTestadmin->setTotal($testId, $this->dbQuestions->getTotalMarks($testId));
        return array('ok' => true, 'inserted' => $inserted);
    }

    private function validateQuestions($sourceText, array $questions)
    {
        if (count($questions) !== 5) {
            return false;
        }

        $normalSource = $this->normaliseWhitespace($sourceText);
        $validated = array();
        foreach ($questions as $question) {
            if (!$this->validCandidate($question)) {
                return false;
            }

            $basis = $this->normaliseWhitespace($question['sourceBasis']);
            if ($basis === '' || mb_stripos($normalSource, $basis, 0, 'UTF-8') === false) {
                return false;
            }

            $options = array_map(function ($option) {
                return trim((string) $option);
            }, $question['options']);
            if (count(array_unique($options)) !== 4) {
                return false;
            }

            $validated[] = array(
                'stem' => trim((string) $question['stem']),
                'options' => $options,
                'correctIndex' => (int) $question['correctIndex'],
                'sourceBasis' => trim((string) $question['sourceBasis'])
            );
        }
        return $validated;
    }

    private function validCandidate(array $question)
    {
        if (!isset($question['stem'], $question['options'], $question['correctIndex'], $question['sourceBasis'])) {
            return false;
        }
        if (trim((string) $question['stem']) === ''
            || trim((string) $question['sourceBasis']) === ''
            || !is_array($question['options'])
            || count($question['options']) !== 4) {
            return false;
        }
        $correct = (int) $question['correctIndex'];
        if ($correct < 0 || $correct > 3) {
            return false;
        }
        foreach ($question['options'] as $option) {
            if (trim((string) $option) === '') {
                return false;
            }
        }
        return true;
    }

    private function normaliseWhitespace($text)
    {
        return trim(preg_replace('/\s+/u', ' ', (string) $text));
    }
}
?>
