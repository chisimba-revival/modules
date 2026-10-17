<?php
/** Atomic, course-bound numerical-question persistence. @author Derek Keats */
if (empty($GLOBALS['kewl_entry_point_run'])) die('No direct access');
class numericalauthoring extends dbTable {
    public function init($tableName=null, $pearDb=null, $errorCallback='globalPearErrorHandler') { parent::init('tbl_test_questions'); }
    public function save($context, $testId, $questionId, array $draft) {
        $text = fn($key) => is_scalar($draft[$key] ?? '') ? trim((string)($draft[$key] ?? '')) : '';
        $number = function($key, $default=null) use ($text) {
            $value=$text($key);if ($value==='' && $default!==null) return $default;
            if (!is_numeric($value) || !is_finite((float)$value)) throw new DomainException('invalid');
            return (float)$value;
        };
        $name=$text('qName');$body=$text('qText');$mark=$number('qMark');$penalty=$number('qPenalty',0);$unitPenalty=$number('penaltyUnit',0);
        if ($name==='' || mb_strlen($name)>250 || trim(strip_tags($body))==='' || $mark<0 || $penalty<0 || $penalty>1 || $unitPenalty<0) throw new DomainException('invalid');
        $answers=array();
        for($i=1;$i<=3;$i++) {
            if ($i>1 && $text('aNumerical'.$i)==='') continue;
            $answer=$number('aNumerical'.$i);$fraction=$number('mark_'.$i,0);
            if ($fraction<0) throw new DomainException('invalid');
            $answers[]=array('answer'=>(string)$answer,'mark'=>$fraction);
        }
        $clean=$this->getObject('script','utilities');
        $fields=array('testid'=>$testId,'name'=>$name,'question'=>$clean->removeScript($body),'questiontext'=>$clean->removeScript($body),'mark'=>$mark,'penalty'=>$penalty,'generalfeedback'=>$clean->removeScript($text('calcqgenfeedback')),'questiontype'=>'numerical');
        $options=array('unitgradingtype'=>$text('unitmode')==='marked'?'yes':'no','showunits'=>in_array($text('dispUnit'),array('yes','no'),true)?$text('dispUnit'):'no','instructions'=>$clean->removeScript($text('instructions')),'unitpenalty'=>$unitPenalty);
        return $this->transaction(function() use ($context,$testId,$questionId,$fields,$answers,$options,$text) {
            $tests=$this->selectPrepared('SELECT id, status FROM tbl_tests WHERE id = ? AND context = ? FOR UPDATE',array($testId,$context));
            if (!$tests || $tests[0]['status']==='open') throw new DomainException('forbidden');
            if ($questionId!=='') {
                $questions=$this->selectPrepared('SELECT id FROM tbl_test_questions WHERE id = ? AND testid = ? AND questiontype = ? FOR UPDATE',array($questionId,$testId,'numerical'));
                if (!$questions) throw new DomainException('forbidden');
                if ($this->update('id',$questionId,$fields)===false) throw new RuntimeException('Question not saved');
            } else {
                $order=$this->selectPrepared('SELECT COALESCE(MAX(questionorder),0) AS n FROM tbl_test_questions WHERE testid = ?',array($testId));
                $fields['questionorder']=(int)$order[0]['n']+1;
                $questionId=$this->insert($fields);if (!$questionId) throw new RuntimeException('Question not saved');
            }
            foreach(array('tbl_test_question_numerical','tbl_test_question_numericaloptions','tbl_test_question_numericalunits') as $table) {
                $this->executePrepared('DELETE FROM '.$table.' WHERE questionid = ?',array($questionId));
            }
            foreach($answers as $answer) if (!$this->insert(array_merge($answer,array('questionid'=>$questionId)),'tbl_test_question_numerical')) throw new RuntimeException('Answer not saved');
            if (!$this->insert(array_merge($options,array('questionid'=>$questionId)),'tbl_test_question_numericaloptions')) throw new RuntimeException('Options not saved');
            if (!$this->insert(array('questionid'=>$questionId,'unit'=>$text('aUnit')),'tbl_test_question_numericalunits')) throw new RuntimeException('Unit not saved');
            $this->executePrepared('UPDATE tbl_tests SET totalmark = (SELECT COALESCE(SUM(mark),0) FROM tbl_test_questions WHERE testid = ?) WHERE id = ?',array($testId,$testId));
            return $questionId;
        });
    }
}
