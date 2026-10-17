<?php
/**
 * @package mcqtests
 * @filesource
 */
// security check - must be included in all scripts
if (!$GLOBALS['kewl_entry_point_run']) {
    die("You cannot view this page directly");
}
// end security check

/**
 * Class for providing access to the table tbl_test_answers in the database
 * @author Megan Watson
 *
 * @copyright (c) 2004 UWC
 * @package mcqtests
 * @version 1.2
 */
class dbanswers extends dbtable
{
    /** Change the selected answer without replacing the answer set. */
    public function selectCorrectAnswer($context,$testId,$questionId,$answerId) {
        return $this->transaction(function() use($context,$testId,$questionId,$answerId) {
            $tests=$this->selectPrepared('SELECT id,status FROM tbl_tests WHERE id=? AND context=? FOR UPDATE',array($testId,$context));
            if (!$tests || $tests[0]['status']==='open') throw new DomainException('forbidden');
            $questions=$this->selectPrepared('SELECT id FROM tbl_test_questions WHERE id=? AND testid=? AND questiontype IN (?,?) FOR UPDATE',array($questionId,$testId,'mcq','tf'));
            $answer=$this->getRow('id',$answerId);
            if (!$questions || !$answer || (string)$answer['questionid']!==(string)$questionId) throw new DomainException('forbidden');
            $this->executePrepared('UPDATE tbl_test_answers SET correct=CASE WHEN id=? THEN 1 ELSE 0 END WHERE questionid=?',array($answerId,$questionId));
            return true;
        });
    }

    /** Save a complete answer set without losing stable IDs or a partial failure. */
    public function saveAnswerSet($context, $testId, $questionId, array $draft, $count) {
        if (filter_var($count, FILTER_VALIDATE_INT) === false || $count < 1 || $count > 50) throw new DomainException('invalid');
        return $this->transaction(function() use ($context,$testId,$questionId,$draft,$count) {
            $tests=$this->selectPrepared('SELECT id,status FROM tbl_tests WHERE id=? AND context=? FOR UPDATE',array($testId,$context));
            if (!$tests || $tests[0]['status']==='open') throw new DomainException('forbidden');
            $questions=$this->selectPrepared('SELECT questiontype FROM tbl_test_questions WHERE id=? AND testid=? FOR UPDATE',array($questionId,$testId));
            if (!$questions || !in_array($questions[0]['questiontype'],array('mcq','tf','freeform'),true)) throw new DomainException('forbidden');
            $freeform=$questions[0]['questiontype']==='freeform';
            $correct=filter_var($draft['correctans']??null,FILTER_VALIDATE_INT);
            if (!$freeform && ($correct===false || $correct<1 || $correct>$count)) throw new DomainException('invalid');
            $existing=$this->selectPrepared('SELECT id FROM tbl_test_answers WHERE questionid=? ORDER BY answerorder,id FOR UPDATE',array($questionId));
            $clean=$this->getObject('script','utilities');
            for ($i=1;$i<=$count;$i++) {
                $answer=$draft['answer'.$i]??'';$comment=$draft['comment'.$i]??'';
                if (!is_string($answer) || !is_string($comment) || strlen($answer)>100000 || strlen($comment)>100000) throw new DomainException('invalid');
                $fields=array('testid'=>$testId,'questionid'=>$questionId,'answer'=>$clean->removeScript($answer),'commenttext'=>$clean->removeScript($comment),'answerorder'=>$i,'correct'=>($freeform || $correct===$i)?1:0,'updated'=>$this->getObject('timeanddateservice','timeanddate-service')->nowStorage());
                if (isset($existing[$i-1])) {
                    if ($this->update('id',$existing[$i-1]['id'],$fields)===false) throw new RuntimeException('Answer not saved');
                } elseif (!$this->insert($fields)) throw new RuntimeException('Answer not saved');
            }
            foreach (array_slice($existing,(int)$count) as $row) if ($this->delete('id',$row['id'])===false) throw new RuntimeException('Answer not removed');
            return true;
        });
    }

    /** Existing service state, explicitly declared for PHP 8.5. */
    public $table;

    /**
     * Method to construct the class and initialise the table
     *
     * @access public
     * @return
     */
    public function init($tableName = null, $pearDb = null, $errorCallback = 'globalPearErrorHandler')
    {
        parent::init('tbl_test_answers');
        $this->table = 'tbl_test_answers';
    }

    /**
     * Method to add a set of answers to the database.
     * If the $id field is not null then the answer is updated.
     *
     * @access public
     * @param array $fields The fields to be inserted.
     * @param string $id The id of the answer to be updated.
     * @return string $id The id of the inserted or updated answer.
     */
    public function addAnswers($fields, $id = NULL)
    {
        
        $fields['updated'] = $this->getObject('timeanddateservice', 'timeanddate-service')->nowStorage();
        if ($id) {
            $this->update('id', $id, $fields);
        } else {
            $id = $this->insert($fields);
        }
        return $id;
    }


    /**
     * Method to delete an answer.
     *
     * @access public
     * @param string $answerId The id of the answer to be deleted.
     * @return
     */
    public function deleteAnswer($answerId)
    {
        $this->delete('id', $answerId);
    }

    /**
     * Method to set or unset the correct answer.
     *
     * @access public
     * @param string $id The id of the answer to set or unset.
     * @param string $set 1 to set, 0 to unset. DEFAULT=1.
     * @return
     */
    public function setCorrect($id, $set = 1)
    {
        $this->update('id', $id, array(
            'correct' => $set
        ));
    }

    /**
     * Method to count the number of answers in a specified question.
     *
     * @access public
     * @param string $answerId The id of the specified question.
     * @return int $num The number of answers in the question.
     */
    public function countAnswers($questionId)
    {
        $sql = "SELECT count(id) AS num FROM ".$this->table." WHERE questionid='$questionId'";
        $data = $this->getArray($sql);
        if (!empty($data)) {
            $num = $data[0]['num'];
            return $num;
        }
        return FALSE;
    }

    /**
     * Method to get the answers for a specific question
     *
     * @access public
     * @param string $questionId The id of the specified question.
     * @return int $array The answers associated with the question.
     */
    public function getAnswers($questionId)
    {
        $answers = $this->getAll("WHERE questionid = '$questionId' ORDER BY answerorder");
        return $answers;
    }

    /**
     * Method to remove existing answers
     *
     * @access public
     * @param string $questionId The id of the specified question.
     * @return int $array The answers associated with the question.
     */
    public function removeAnswers($questionId)
    {
        return $this->delete('questionid', $questionId);
    }

    public function getCorrectAnswer($questionId)
    {
        $answers = $this->getAll("WHERE questionid = '$questionId' AND correct='1' ORDER BY answerorder");

        if ((is_countable($answers) ? count($answers) : 0) == 0) {
            return 0;
        } else {
            return $answers[0]['answerorder'];
        }
    }

    public function getAlternativeAnswers($testId, $questionId)
    {
        $sql = "SELECT answer
        FROM tbl_test_answers
        WHERE testid='{$testId}'
        AND questionid='{$questionId}'
        AND answerorder <> 1
        ORDER BY answerorder ASC";
        $data = $this->getArray($sql);
        return empty($data)?FALSE:$data;
    }

} // end of class
?>
