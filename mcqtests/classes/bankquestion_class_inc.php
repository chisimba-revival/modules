<?php
/** Stable question identity, independent of answer order and presentation. @author Derek Keats */
if (empty($GLOBALS['kewl_entry_point_run'])) die('No direct access');
class bankquestion extends ChisimbaObject
{
    public function normalise($text)
    {
        $text = preg_replace('/<img\b[^>]*\bsrc=["\']([^"\']+)["\'][^>]*>/iu', ' [image:$1] ', (string)$text);
        $text = preg_replace('/<\/?(?:p|div|br|li|h[1-6])\b[^>]*>/iu', ' ', $text);
        $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        return mb_strtolower(trim(preg_replace('/[\s\p{Z}]+/u', ' ', $text)), 'UTF-8');
    }
    public function validate(array $question)
    {
        if ($this->normalise($question['stem'] ?? '') === '' || !in_array($question['type'] ?? '', ['mcq','tf'], true)) throw new DomainException('bank_invalid');
        $answers = $question['answers'] ?? [];
        if (count($answers) < 2 || count($answers) > 20) throw new DomainException('bank_invalid');
        $correct = 0;
        foreach ($answers as $answer) {
            if ($this->normalise($answer['answer'] ?? '') === '') throw new DomainException('bank_invalid');
            $correct += !empty($answer['correct']) ? 1 : 0;
        }
        if ($correct !== 1 || (int)($question['mark'] ?? 0) < 1) throw new DomainException('bank_invalid');
        return $question;
    }
    public function stemKey(array $question) { return hash('sha256', $this->normalise($question['stem'])); }
    /** Existing test tables may still use three-byte UTF-8; entities preserve all characters. */
    public function testText($text)
    {
        return mb_encode_numericentity((string)$text, [0x10000, 0x10ffff, 0, 0xffffff], 'UTF-8');
    }
    public function fingerprint(array $question)
    {
        $this->validate($question);
        $answers = [];
        foreach ($question['answers'] as $answer) $answers[] = [$this->normalise($answer['answer']), !empty($answer['correct'])];
        sort($answers);
        return hash('sha256', json_encode([$this->normalise($question['stem']), $answers], JSON_THROW_ON_ERROR));
    }
    /** Keep the complete generated candidate and source reference for teaching staff. */
    public function generated(array $question, array $set, $index, $chapter)
    {
        $encode = static fn($text) => htmlspecialchars((string)$text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $answers = [];
        foreach ($question['options'] as $i => $answer) $answers[] = ['answer'=>$encode($answer),'correct'=>$i === (int)$question['correctIndex'] ? 1 : 0,'commenttext'=>''];
        return $this->validate(['stem'=>$encode($question['stem']),'type'=>'mcq','mark'=>1,'hint'=>'','answers'=>$answers,
            'metadata'=>['candidate'=>$question,'source'=>['module'=>'questiongenerator','setid'=>$set['id'],'version'=>$set['version'],'title'=>$set['title'],'index'=>$index]],
            'chapters'=>$chapter === '' ? [] : [$chapter]]);
    }
}
