<?php
/** AI-assisted, lecturer-reviewed Essay marking. @package essay */
if (empty($GLOBALS['kewl_entry_point_run'])) { die('You cannot view this page directly'); }

/** Generates criterion-level drafts without writing authoritative marks. @author Derek Keats */
class essayaimarker extends ChisimbaObject
{
    private $aiService = null;
    /** @return bool Whether the shared AI service is configured. */
    public function isAvailable() { return $this->ensureAiAvailable(); }

    /**
     * Generate an editable marking draft and non-diagnostic authorship review.
     *
     * @return array Result containing the draft or a stable error code.
     */
    public function suggest(array $topic, array $essay, array $booking, array $rubric)
    {
        if (!$this->ensureAiAvailable()) { return array('ok'=>false, 'error'=>'ai_unavailable'); }
        $submission = trim((string) ($booking['submission_text'] ?? ''));
        if ($submission === '') { return array('ok'=>false, 'error'=>'text_unavailable'); }
        $criteria = array();
        foreach ((array) ($rubric['criteria'] ?? array()) as $index => $criterion) {
            $criteria[] = array(
                'criterionId'=>'criterion-'.($index + 1),
                'objective'=>(string) ($criterion['objective'] ?? ''),
                'maximumMark'=>max(1, (int) ($criterion['maximumMark'] ?? 1)),
                'levels'=>$criterion['levels'] ?? array(),
            );
        }
        if ($criteria === array()) { return array('ok'=>false, 'error'=>'rubric_unavailable'); }
        $schema = array('type'=>'object','properties'=>array(
            'criteria'=>array('type'=>'array','items'=>array('type'=>'object','properties'=>array(
                'criterionId'=>array('type'=>'string'), 'score'=>array('type'=>'integer','minimum'=>0),
                'rationale'=>array('type'=>'string')
            ),'required'=>array('criterionId','score','rationale'),'additionalProperties'=>false)),
            'feedback'=>array('type'=>'string'),
            'strengths'=>array('type'=>'array','items'=>array('type'=>'string')),
            'improvements'=>array('type'=>'array','items'=>array('type'=>'string')),
            'authorshipReview'=>array('type'=>'object','properties'=>array(
                'recommended'=>array('type'=>'boolean'),
                'observations'=>array('type'=>'array','items'=>array('type'=>'string')),
                'questions'=>array('type'=>'array','items'=>array('type'=>'string'))
            ),'required'=>array('recommended','observations','questions'),'additionalProperties'=>false)
        ),'required'=>array('criteria','feedback','strengths','improvements','authorshipReview'),'additionalProperties'=>false);
        $input = array(
            'topicArea'=>(string) ($topic['name'] ?? ''),
            'brief'=>strip_tags((string) ($topic['instructions'] ?? '')),
            'essayTitle'=>(string) ($essay['topic'] ?? ''),
            'essayGuidance'=>strip_tags((string) ($essay['notes'] ?? '')),
            'modelEssay'=>strip_tags((string) ($essay['model_essay'] ?? '')),
            'studentEssay'=>$submission,
            'rubric'=>array('title'=>$rubric['title'] ?? 'Essay rubric','criteria'=>$criteria),
        );
        $instructions = 'Prepare an editable marking draft for a human lecturer. Score every rubric criterion independently from zero to its maximumMark and give submission-specific evidence for the score. The model essay is a reference for expected knowledge and depth, not a wording template: do not reward textual similarity or penalise a different valid argument. Reserve excellent scores for comprehensive, accurate, analytical work; distinguish polished language from depth of understanding. Never claim the result is final. The authorshipReview is not an AI detector: never classify the author as AI or human, never give a probability, and never alter scores because of it. A short response, factual errors, weak depth, unsupported claims or informal language are marking matters and must not by themselves make recommended true. Recommend a separate authorship/source discussion only for a concrete internal inconsistency relevant to provenance, such as an abrupt unexplained change in voice, unverifiable or fabricated-looking citations, or claims about sources or methods that need verification. State observations neutrally and provide questions the lecturer could ask.';
        $result = $this->aiService->execute(array(
            'consumer'=>'essay', 'task'=>'suggest_essay_mark', 'instructions'=>$instructions,
            'input'=>json_encode($input), 'schemaName'=>'essay_marking_suggestion_v2', 'schema'=>$schema
        ));
        if (empty($result['ok']) || !is_array($result['data'] ?? null)) {
            return array('ok'=>false,'error'=>(string)($result['error'] ?? 'provider_failed'));
        }
        $byId = array(); foreach ($criteria as $criterion) { $byId[$criterion['criterionId']] = $criterion; }
        $scored = array(); $total = 0;
        foreach ((array) ($result['data']['criteria'] ?? array()) as $item) {
            $id = (string) ($item['criterionId'] ?? '');
            if (!isset($byId[$id]) || isset($scored[$id])) { continue; }
            $score = min($byId[$id]['maximumMark'], max(0, (int) ($item['score'] ?? 0)));
            $scored[$id] = array(
                'criterionId'=>$id, 'objective'=>$byId[$id]['objective'], 'score'=>$score,
                'maximumMark'=>$byId[$id]['maximumMark'],
                'rationale'=>trim((string)($item['rationale'] ?? '')),
            );
            $total += $score;
        }
        if (count($scored) !== count($criteria)) { return array('ok'=>false,'error'=>'incomplete_suggestion'); }
        $review = (array) ($result['data']['authorshipReview'] ?? array());
        return array('ok'=>true,'suggestion'=>array(
            'mark'=>max(0,min(100,$total)), 'criteria'=>array_values($scored),
            'feedback'=>trim((string)($result['data']['feedback'] ?? '')),
            'strengths'=>array_values((array)($result['data']['strengths'] ?? array())),
            'improvements'=>array_values((array)($result['data']['improvements'] ?? array())),
            'authorshipReview'=>array(
                'recommended'=>!empty($review['recommended']),
                'observations'=>array_values((array)($review['observations'] ?? array())),
                'questions'=>array_values((array)($review['questions'] ?? array())),
            ),
        ));
    }

    /** @return bool */
    private function ensureAiAvailable()
    {
        if ($this->aiService !== null) {
            return method_exists($this->aiService,'isAvailable') && $this->aiService->isAvailable();
        }
        try {
            if (!$this->getObject('modules','modulecatalogue')->checkIfRegistered('ai')) { return false; }
            $this->aiService=$this->getObject('aiservice','ai');
            return method_exists($this->aiService,'isAvailable') && $this->aiService->isAvailable();
        } catch (Throwable $error) { $this->aiService=null; return false; }
    }
}
?>
