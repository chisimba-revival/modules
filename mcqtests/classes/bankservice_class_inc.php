<?php
/** Reusable banks: teaching permissions, independent copies and collision handling. @author Derek Keats */
if (empty($GLOBALS['kewl_entry_point_run'])) die('No direct access');
class bankservice extends ChisimbaObject
{
    private function store() { return $this->getObject('bankstore','mcqtests'); }
    private function codec() { return $this->getObject('bankquestion','mcqtests'); }
    public function teaches($context)
    {
        $user=$this->getObject('user','security');
        return $context!=='' && $context!=='root' && $user->isLoggedIn()
            && $this->getObject('dbcontext','context')->getContextDetails($context)
            && ($user->isAdmin() || $user->isCourseAdmin($context) || $user->isContextLecturer($user->userId(),$context));
    }
    public function requireCourse($context) { if (!$this->teaches($context)) throw new DomainException('bank_forbidden'); }
    public function author()
    {
        $user=$this->getObject('user','security');
        return $user->isLoggedIn() && ($user->isAdmin() || $user->isLecturer());
    }
    public function manages(array $bank)
    {
        $user=$this->getObject('user','security');
        return $user->isLoggedIn() && ($user->isAdmin() || (string)$bank['createdby']===(string)$user->userId()
            || in_array((string)$user->userId(),json_decode($bank['managers_json'],true),true));
    }
    /** Bind a grant to the course's stable ID, so reusing a deleted course code grants nothing. */
    public function sharedWith(array $bank,$context)
    {
        $course=$this->getObject('dbcontext','context')->getContextDetails($context);
        $shares=json_decode($bank['shares_json'],true);
        return $course && (isset($shares['*']) || ($shares[$context]??null)===(string)$course['id']);
    }
    public function access($id,$context,$write=false,$lock=false)
    {
        $bank=$this->store()->bank($id,$lock);
        if (!$bank || (!$this->manages($bank) && ($write || !$this->teaches($context)
            || !$this->sharedWith($bank,$context)))) throw new DomainException('bank_forbidden');
        return $bank;
    }
    public function available($context,$write=false)
    {
        $teaches=$this->teaches($context);
        return array_values(array_filter($this->store()->banks(),fn($bank)=>$this->manages($bank)
            || (!$write && $teaches && $this->sharedWith($bank,$context))));
    }
    public function create($context,$name)
    {
        if (!$this->author()) throw new DomainException('bank_forbidden');
        if ($context!=='' && !$this->teaches($context)) $context='';
        $name=trim((string)$name);
        if ($name==='' || mb_strlen($name)>200) throw new DomainException('bank_name_error');
        $shares=$context===''?[]:[$context=>(string)$this->getObject('dbcontext','context')->getContextDetails($context)['id']];
        return $this->store()->addBank($shares,$name,(string)$this->getObject('user','security')->userId());
    }
    public function share($id,$context,array $courses,$version,array $usernames=[])
    {
        return $this->store()->atomic(function()use($id,$context,$courses,$version,$usernames){
            $bank=$this->access($id,$context,true,true);
            if ((string)$bank['version']!==(string)$version) throw new DomainException('bank_changed');
            $shares=[];
            foreach ($courses as $course) {
                if ($course==='*') {
                    if (!$this->getObject('user','security')->isAdmin() && !isset(json_decode($bank['shares_json'],true)['*'])) throw new DomainException('bank_forbidden');
                    $shares['*']='*';continue;
                }
                if (!is_string($course) || $course==='root' || !$this->getObject('dbcontext','context')->getContextDetails($course)) throw new DomainException('bank_course');
                $shares[$course]=(string)$this->getObject('dbcontext','context')->getContextDetails($course)['id'];
            }
            $managers=[];
            foreach ($usernames as $username) {
                $person=$this->getObject('userservice','security')->findByUsername($username);
                if (!$person) throw new DomainException('bank_manager');
                $managers[]=(string)$person['userid'];
            }
            $s=$this->store(); $s->run('UPDATE tbl_mcq_banks SET version=version+1,managers_json='.$s->q(json_encode(array_values(array_unique($managers)),JSON_THROW_ON_ERROR)).',shares_json='.$s->q(json_encode($shares,JSON_THROW_ON_ERROR)).' WHERE id='.$s->q($id));
        });
    }
    public function items($id,$context,$chapter='')
    {
        $this->access($id,$context); $items=$this->store()->items($id);
        foreach ($items as &$item) $item['content']=json_decode($item['content_json'],true,512,JSON_THROW_ON_ERROR);
        unset($item);
        return array_values(array_filter($items,static fn($item)=>$chapter==='' || in_array($chapter,$item['content']['chapters']??[],true)));
    }
    /** Read the complete persisted test and recheck its course on every operation. */
    public function test($id,$context,$write=false,$lock=false)
    {
        $this->requireCourse($context); $s=$this->store();
        $test=$s->rows('SELECT * FROM tbl_tests WHERE id='.$s->q($id).($lock?' FOR UPDATE':''))[0]??null;
        if (!$test || $test['context']!==$context) throw new DomainException('bank_forbidden');
        if ($write && $test['status']==='open') throw new DomainException('bank_open');
        return $test;
    }
    public function tests($context)
    {
        $this->requireCourse($context); $s=$this->store();
        return $s->rows('SELECT id,name,chapter,status FROM tbl_tests WHERE context='.$s->q($context).' ORDER BY name,id');
    }
    public function fromTest($id,$context)
    {
        $test=$this->test($id,$context); $s=$this->store(); $questions=[];$chapter='';
        if (!empty($test['chapter'])) {
            $row=$this->getObject('db_contextcontent_contextchapter','contextcontent')->getChapter($test['chapter'],$context);
            $chapter=(string)($row['chaptertitle']??$test['chapter']);
        }
        foreach ($this->getObject('dbquestions','mcqtests')->getQuestions($id) ?: [] as $row) {
            if (!in_array($row['questiontype'],['mcq','tf'],true)) continue;
            $answers=$s->rows('SELECT answer,correct,commenttext FROM tbl_test_answers WHERE questionid='.$s->q($row['id']).' ORDER BY answerorder,id');
            $stored=$s->metadata($row['id']);
            $metadata=$stored['metadata']??$stored;
            $questions[]=$this->codec()->validate(['stem'=>$row['question'],'type'=>$row['questiontype'],'mark'=>(int)$row['mark'],'hint'=>$row['hint']??'','needsreview'=>(int)($row['needsreview']??0),'generalfeedback'=>$row['generalfeedback']??'','answers'=>$answers,
                'metadata'=>$metadata+['source'=>['module'=>'mcqtests','testid'=>$id,'questionid'=>$row['id']]],
                'provenance'=>$stored['provenance']??[],
                'chapters'=>array_values(array_unique(array_merge($stored['chapters']??[],$chapter===''?[]:[$chapter])))]);
        }
        return $questions;
    }
    public function fromSet($id,$context,$version=null,$indices=null,$chapter='')
    {
        $set=$this->getObject('workshopservice','questiongenerator')->read($id);
        if (($set['question_type']??'mcq')!=='mcq' || empty($set['reviewed'])) throw new DomainException('bank_review');
        if ($version!==null && (string)$version!==(string)$set['version']) throw new DomainException('bank_changed');
        if (mb_strlen($chapter)>200) throw new DomainException('bank_invalid');
        if ($indices!==null) foreach($indices as $index) if(!is_string($index)||!ctype_digit($index)) throw new DomainException('bank_selection');
        $questions=[];
        foreach (json_decode($set['questions_json'],true,512,JSON_THROW_ON_ERROR) as $index=>$question) {
            if (!($question['included']??true) || ($indices!==null && !in_array((string)$index,$indices,true))) continue;
            $questions[]=$this->codec()->generated($question,$set,$index,$chapter);
        }
        if($indices!==null && count($questions)!==count(array_unique($indices)))throw new DomainException('bank_selection');
        return ['set'=>$set,'questions'=>$questions];
    }
    /** Serialising on the bank prevents two concurrent submissions making duplicates. */
    public function add($id,$context,array $questions)
    {
        if (!$questions || count($questions)>1000) throw new DomainException('bank_selection');
        return $this->store()->atomic(function()use($id,$context,$questions){
            $this->access($id,$context,true,true); $s=$this->store(); $codec=$this->codec();
            $existing=$s->items($id); $byHash=[];$byStem=[];
            foreach ($existing as $item) { $byHash[$item['fingerprint']]=$item; $byStem[$item['stemkey']]=true; }
            $result=['added'=>0,'duplicates'=>0,'conflicts'=>0];
            foreach ($questions as $question) {
                $hash=$codec->fingerprint($question); $stem=$codec->stemKey($question);
                if (isset($byHash[$hash])) {
                    $item=$byHash[$hash];$content=json_decode($item['content_json'],true,512,JSON_THROW_ON_ERROR);
                    $content['chapters']=array_values(array_unique(array_merge($content['chapters']??[],$question['chapters']??[])));
                    // Retain additional provenance without changing the first saved question.
                    $variants=$content['provenance']??[];$variants[]=$question['metadata']??[];
                    $content['provenance']=array_values(array_unique($variants,SORT_REGULAR));
                    $json=json_encode($content,JSON_THROW_ON_ERROR);$s->run('UPDATE tbl_mcq_bank_items SET content_json='.$s->q($json).' WHERE id='.$s->q($item['id']));
                    $byHash[$hash]['content_json']=$json;$result['duplicates']++;continue;
                }
                if (isset($byStem[$stem])) { $result['conflicts']++; $result['conflictQuestions'][]=$question['stem'];continue; }
                $new=$s->addItem($id,$question,$hash,$stem);
                $byHash[$hash]=['id'=>$new,'content_json'=>json_encode($question,JSON_THROW_ON_ERROR)];$byStem[$stem]=true;$result['added']++;
            }
            return $result;
        });
    }
    /** Test copies and private metadata are written atomically; bank copies remain intact. */
    public function pull($id,$context,$testid,array $ids)
    {
        if (!$ids || count($ids)>1000) throw new DomainException('bank_selection');
        return $this->store()->atomic(function()use($id,$context,$testid,$ids){
            $this->access($id,$context,false,true);$this->test($testid,$context,true,true);
            $s=$this->store();$codec=$this->codec();$available=[];
            foreach ($this->items($id,$context) as $item) $available[$item['id']]=$item;
            foreach ($ids as $selected) if (!is_string($selected) || !isset($available[$selected])) throw new DomainException('bank_forbidden');
            $hashes=[];$stems=[];
            foreach ($this->fromTest($testid,$context) as $question) { $hashes[$codec->fingerprint($question)]=true;$stems[$codec->stemKey($question)]=true; }
            $dbq=$this->getObject('dbquestions','mcqtests');$dba=$this->getObject('dbanswers','mcqtests');
            $order=(int)$dbq->getMaxOrder($testid);$result=['added'=>0,'duplicates'=>0,'conflicts'=>0];
            foreach (array_unique($ids) as $selected) {
                $item=$available[$selected];$question=$item['content'];$hash=$codec->fingerprint($question);$stem=$codec->stemKey($question);
                if (isset($hashes[$hash])) { $result['duplicates']++;continue; }
                if (isset($stems[$stem])) { $result['conflicts']++;$result['conflictQuestions'][]=$question['stem'];continue; }
                $qid=$dbq->addQuestion(['testid'=>$testid,'question'=>$codec->testText($question['stem']),'questiontext'=>mb_substr($codec->testText(strip_tags($question['stem'])),0,255),'hint'=>$codec->testText($question['hint']??''),'mark'=>$question['mark'],'questionorder'=>++$order,'questiontype'=>$question['type'],'qtype'=>$question['type'],'needsreview'=>(int)($question['needsreview']??0),'generalfeedback'=>$codec->testText($question['generalfeedback']??'')]);
                if (!$qid || !$dbq->getRow('id',$qid)) throw new RuntimeException('bank_storage');
                foreach ($question['answers'] as $n=>$answer) {
                    $aid=$dba->addAnswers(['testid'=>$testid,'questionid'=>$qid,'answer'=>$codec->testText($answer['answer']),'correct'=>(int)!empty($answer['correct']),'commenttext'=>mb_substr($codec->testText($answer['commenttext']??''),0,120),'answerorder'=>$n+1]);
                    if (!$aid || !$dba->getRow('id',$aid)) throw new RuntimeException('bank_storage');
                }
                $s->saveMetadata($qid,['bankid'=>$id,'itemid'=>$selected,'metadata'=>$question['metadata']??[],'provenance'=>$question['provenance']??[],'chapters'=>$question['chapters']??[]]);
                $hashes[$hash]=true;$stems[$stem]=true;$result['added']++;
            }
            $this->getObject('dbtestadmin','mcqtests')->setTotal($testid,$dbq->getTotalMarks($testid));
            return $result;
        });
    }
}
