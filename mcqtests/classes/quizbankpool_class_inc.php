<?php
/** Chapter banks, fixed draws and durable per-attempt selections. @author Derek Keats */
if(empty($GLOBALS['kewl_entry_point_run']))die('No direct access');
class quizbankpool extends dbTable
{
    public function init($tableName=null,$pearDb=null,$errorCallback='globalPearErrorHandler'){parent::init('tbl_mcq_quiz_banks',$pearDb,$errorCallback);}
    public function config($testId){return $this->selectPrepared('SELECT * FROM tbl_mcq_quiz_banks WHERE id=?',[$testId])[0]??null;}
    /** Fisher-Yates: sampling without replacement, independent of question order. */
    public function sample(array $items,$count)
    {
        if(!is_int($count)||$count<1||$count>count($items))throw new DomainException('bank_pool_size');
        $items=array_values($items);for($i=count($items)-1;$i>0;$i--){$j=random_int(0,$i);[$items[$i],$items[$j]]=[$items[$j],$items[$i]];}
        return array_slice($items,0,$count);
    }
    /** Called only after the worker's owner, chapter and unchanged-target checks. */
    public function publish(array $job,array $questions,array $settings)
    {
        $settings=$this->getObject('quizinputplan','mcqtests')->settings($settings);
        if(count($questions)!==$settings['bankCount'])throw new DomainException('bank_pool_size');
        return $this->transactionWithSavepoint(function()use($job,$questions,$settings){
            $test=$this->selectPrepared('SELECT * FROM tbl_tests WHERE id=? FOR UPDATE',[$job['testid']])[0]??null;
            if(!$test)throw new DomainException('quiz_missing');
            if($test['context']!==$job['contextcode'])throw new DomainException('quiz_context_changed');
            if($test['status']!=='inactive')throw new DomainException('quiz_status_changed');
            if($this->config($test['id']))throw new DomainException('quiz_bank_exists');
            if($this->selectPrepared('SELECT id FROM tbl_test_questions WHERE testid=?',[$test['id']]))throw new DomainException('quiz_questions_exist');
            $course=$this->getObject('dbcontext','context')->getContext($job['contextcode']);
            if(!$course||!$this->getObject('contextauthorservice','contextadmin')->isAuthor($job['contextcode'],$job['userid']))throw new DomainException('forbidden');
            $chapter=$this->selectPrepared('SELECT chaptertitle FROM tbl_contextcontent_chaptercontent WHERE chapterid=? ORDER BY original DESC LIMIT 1',[$test['chapter']])[0]??null;
            $store=$this->getObject('bankstore','mcqtests');$codec=$this->getObject('bankquestion','mcqtests');
            $bank=$store->addBank([$job['contextcode']=>(string)$course['id']],$test['name'],$job['userid']);
            $candidates=[];
            foreach($questions as $index=>$question){
                $content=$codec->generated($question,['id'=>$job['id'],'version'=>1,'title'=>$test['name']],$index,$chapter['chaptertitle']??$test['chapter']);
                $content['metadata']['source']['module']='knowledgemap';$content['metadata']['source']['chapterid']=$test['chapter'];
                $item=$store->addItem($bank,$content,$codec->fingerprint($content),$codec->stemKey($content));
                $question['bankid']=$bank;$question['itemid']=$item;$candidates[]=$question;
            }
            $chosen=$settings['selection']==='fixed'?$this->sample($candidates,$settings['drawCount']):$candidates;
            foreach(array_chunk($chosen,30) as $batch){$result=$this->getObject('mcqaigenerator','mcqtests')->insertQuestions($test['id'],$batch,$job['userid']);if(empty($result['ok']))throw new RuntimeException('bank_storage');}
            if(!$this->insert(['id'=>$test['id'],'bankid'=>$bank,'contextcode'=>$test['context'],'chapterid'=>$test['chapter'],'bank_count'=>count($questions),'draw_count'=>$settings['drawCount'],'selection'=>$settings['selection']]))throw new RuntimeException('bank_storage');
            $this->executeExactPrepared('UPDATE tbl_tests SET totalmark=? WHERE id=?',[$settings['drawCount'],$test['id']]);
            return $bank;
        });
    }
    /** Read an existing paper only: resuming and grading can never redraw it. */
    public function paper($resultId,$userId,$testId)
    {
        $user=$this->getObject('user','security');
        if((string)$user->userId()!==(string)$userId){
            $test=$this->selectPrepared('SELECT context FROM tbl_tests WHERE id=?',[$testId])[0]??null;
            if(!$test||!$this->getObject('contextauthorservice','contextadmin')->canManageContext($test['context'],$user->userId()))throw new DomainException('forbidden');
        }
        $row=$this->selectPrepared('SELECT paper_json FROM tbl_mcq_attempt_papers WHERE id=? AND userid=? AND testid=?',[$resultId,$userId,$testId])[0]??null;
        return $row?json_decode($row['paper_json'],true,512,JSON_THROW_ON_ERROR):null;
    }
    public function totalForResult($resultId,$fallback)
    {return ($this->selectPrepared('SELECT totalmark FROM tbl_mcq_attempt_papers WHERE id=?',[$resultId])[0]['totalmark']??$fallback);}
    /** Bind one draw to the owned attempt under its row lock, across sessions/tabs. */
    public function ensurePaper($resultId,$userId,$testId)
    {
        if((string)$this->getObject('user','security')->userId()!==(string)$userId)throw new DomainException('forbidden');
        return $this->transactionWithSavepoint(function()use($resultId,$userId,$testId){
            $attempt=$this->selectPrepared('SELECT * FROM tbl_test_results WHERE id=? AND studentid=? AND testid=? FOR UPDATE',[$resultId,$userId,$testId])[0]??null;
            if(!$attempt||(int)$attempt['mark']!==-1)throw new DomainException('attempt_unavailable');
            $existing=$this->paper($resultId,$userId,$testId);if($existing)return $existing;
            $this->selectPrepared('SELECT id FROM tbl_tests WHERE id=? FOR UPDATE',[$testId]);
            $paper=$this->buildPaper($testId);
            if(!$this->insert(['id'=>$resultId,'testid'=>$testId,'userid'=>$userId,'paper_json'=>json_encode($paper,JSON_THROW_ON_ERROR),'totalmark'=>$paper['totalmark']],'tbl_mcq_attempt_papers'))throw new RuntimeException('paper_storage');
            return $paper;
        });
    }
    public function previewPaper($testId)
    {
        $test=$this->getObject('dbtestadmin','mcqtests')->getRow('id',$testId);
        if(!$test || !$this->getObject('contextauthorservice','contextadmin')->canManageContext($test['context'],$this->getObject('user','security')->userId()))throw new DomainException('forbidden');
        return $this->buildPaper($testId);
    }
    private function buildPaper($testId)
    {
        $config=$this->config($testId);if(!$config)throw new DomainException('bank_pool_missing');
        $test=$this->selectPrepared('SELECT * FROM tbl_tests WHERE id=?',[$testId])[0]??null;
        $questions=$this->selectPrepared('SELECT * FROM tbl_test_questions WHERE testid=? ORDER BY questionorder,id',[$testId]);
        if($config['selection']==='per_attempt')$questions=$this->sample($questions,(int)$config['draw_count']);
        elseif(count($questions)!==(int)$config['draw_count'])throw new DomainException('bank_pool_size');
        if(($test['qsequence']??'')==='Scrambled')$questions=$this->sample($questions,count($questions));
        else usort($questions,fn($a,$b)=>(int)$a['questionorder']<=>(int)$b['questionorder'] ?: strcmp($a['id'],$b['id']));
        $total=0;
        foreach($questions as $i=>&$q){
            if(!in_array($q['questiontype'],['mcq','tf'],true))throw new DomainException('bank_pool_type');
            if((int)$q['mark']!==1)throw new DomainException('bank_pool_marks');
            $q['questionorder']=$i+1;$q['count']=count($questions);$total+=(int)$q['mark'];
            $answers=$this->selectPrepared('SELECT * FROM tbl_test_answers WHERE questionid=? AND testid=? ORDER BY answerorder,id',[$q['id'],$testId]);
            if(count(array_filter($answers,fn($a)=>(int)$a['correct']===1))!==1)throw new DomainException('bank_pool_answers');
            if(($test['asequence']??'')==='Scrambled')$answers=$this->sample($answers,count($answers));
            foreach($answers as $j=>&$answer)$answer['answerorder']=$j+1;unset($answer);$q['answers']=$answers;
        }unset($q);
        if($total<1)throw new DomainException('bank_pool_marks');
        return ['questions'=>$questions,'totalmark'=>$total];
    }
    public function question(array $paper,$id){foreach($paper['questions'] as $q)if($q['id']===$id)return $q;return null;}
    public function grade(array $paper,$resultId)
    {
        $selected=$this->selectPrepared('SELECT questionid,answerid FROM tbl_test_marked WHERE resultid=?',[$resultId]);$answers=array_column($selected,'answerid','questionid');$total=0;
        foreach($paper['questions'] as $q)foreach($q['answers'] as $answer)if(($answers[$q['id']]??null)===$answer['id']&&(int)$answer['correct']===1)$total+=(int)$q['mark'];
        return $total;
    }
    public function page(array $paper,$resultId,$offset,$limit=10)
    {
        $selected=$this->selectPrepared('SELECT id,questionid,answerid FROM tbl_test_marked WHERE resultid=?',[$resultId]);$byQuestion=array_column($selected,null,'questionid');
        $data=array_slice($paper['questions'],max(0,(int)$offset),$limit);
        foreach($data as &$q){foreach($q['answers'] as &$answer){if(($byQuestion[$q['id']]['answerid']??null)===$answer['id'])$answer['selected']=$byQuestion[$q['id']]['id'];}unset($answer);$q['qnum']=max(0,(int)$offset);}unset($q);
        return $data;
    }
    public function review(array $paper,$resultId,$offset)
    {
        $selected=$this->selectPrepared('SELECT questionid,answerid FROM tbl_test_marked WHERE resultid=?',[$resultId]);$byQuestion=array_column($selected,'answerid','questionid');$rows=[];
        foreach(array_slice($paper['questions'],max(0,(int)$offset),10) as $q){
            $correct=null;$chosen=null;foreach($q['answers'] as $a){if((int)$a['correct']===1)$correct=$a;if(($byQuestion[$q['id']]??null)===$a['id'])$chosen=$a;}
            $row=$q;unset($row['answers']);$row+=['questionid'=>$q['id'],'answer'=>$correct['answer'],'answerorder'=>$correct['answerorder'],'studcomment'=>$chosen['commenttext']??'','studcorrect'=>$chosen['correct']??0,'studans'=>$chosen['answer']??'','studorder'=>$chosen['answerorder']??0,'visible'=>true];$rows[]=$row;
        }
        return $rows;
    }
}
