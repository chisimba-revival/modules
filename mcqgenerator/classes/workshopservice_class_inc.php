<?php
/** Private question-set lifecycle using the shared MCQ AI consumer. @author Derek Keats */
if(empty($GLOBALS['kewl_entry_point_run']))die('No direct access');
class workshopservice extends ChisimbaObject
{
    public function read($id)
    {
        $row=$this->getObject('workshopstore')->one($id);
        if(!$row || !$this->getObject('workshoppolicy')->owner($row))throw new DomainException('not_found');
        return $row;
    }
    public function title($title)
    {
        $title=trim((string)$title);
        if($title==='' || mb_strlen($title)>200 || !mb_check_encoding($title,'UTF-8') || preg_match('/[\x00-\x1F]/',$title))throw new DomainException('title_invalid');
        return $title;
    }
    public function generate($id)
    {
        $row=$this->read($id);$store=$this->getObject('workshopstore');
        if(!$store->claim($row))throw new DomainException('generation_busy');
        try {
            $result=$this->getObject('mcqaigenerator','mcqtests')->generate($row['source_text'],(int)$row['question_count']);
            if(empty($result['ok'])){
                $code=$result['error']??'';
                if($code==='grounding_validation_failed' && !empty($result['candidates'])){
                    $candidates=$this->candidates($result['candidates'],$result['issues']??[]);
                    $store->finish($row,$candidates,$result['issues']??[]);
                    return;
                }
                throw new DomainException($code==='grounding_validation_failed'?'generation_grounding':($code==='ai_unavailable'?'generation_unavailable':'generation_provider'));
            }
            $questions=$this->validate($result['questions'],(int)$row['question_count']);
            $store->finish($row,$questions);
        }catch(Throwable $e){
            $store->failed($row,isset($result['issues'])&&is_array($result['issues'])?$result['issues']:[]);
            $known=['generation_grounding','generation_unavailable','generation_provider','questions_invalid','storage_failed'];
            throw new DomainException(in_array($e->getMessage(),$known,true)?$e->getMessage():'generation_failed');
        }
    }
    public function begin($id){
        $row=$this->read($id);
        $capacity=$this->getObject('aicapacity','ai')->forTextGeneration();
        $job=$this->getObject('workshopplan')->build($row['source_text'],(int)$row['question_count'],$capacity);
        $store=$this->getObject('workshopstore');
        if(!$store->claim($row))throw new DomainException('generation_busy');
        $store->saveJob($row,$job);
    }
    /** Exactly one paid request per claimed section; never replay an uncertain request. */
    public function part($id){
        $row=$this->read($id);$store=$this->getObject('workshopstore');
        $job=json_decode($row['generation_json']??'',true);
        if(!$job||!$store->claimPart($row))throw new DomainException('generation_busy');
        try{
            $capacity=$this->getObject('aicapacity','ai')->forTextGeneration();
            if($capacity!==$job['capacity'])throw new DomainException('capacity_changed');
            $i=$job['next'];
            $result=$this->getObject('mcqaigenerator','mcqtests')->generate($job['parts'][$i],$job['counts'][$i],$capacity['outputTokens']);
            $questions=$result['questions']??$result['candidates']??[];
            if(!$questions)throw new DomainException('generation_provider');
            $issues=$result['issues']??[];$offset=count($job['questions']);
            $job['questions']=array_merge($job['questions'],$this->candidates($questions,$issues));
            foreach($issues as $issue){if(isset($issue['question']))$issue['question']+=$offset;$job['issues'][]=$issue;}
            $job['next']++;
            // Persist this result before allowing the next section to start.
            $store->saveJob($row,$job,$job['next']===count($job['parts'])?'processing':'generating');
            if($job['next']===count($job['parts'])){
                // If many sections needed one candidate each, keep the requested
                // number spread through the chapter; retain others for review.
                if(count($job['questions'])>$job['requested']){
                    $n=count($job['questions']);$keep=[];
                    for($j=0;$j<$job['requested'];$j++)$keep[(int)floor(($j+.5)*$n/$job['requested'])]=true;
                    foreach($job['questions'] as $j=>&$q)$q['included']=$q['included']&&isset($keep[$j]);unset($q);
                }
                $store->finish($row,$job['questions'],$job['issues']);
            }
        }catch(Throwable $error){
            if($job['questions']){
                $job['issues'][]=['code'=>'partial'];
                $store->finish($row,$job['questions'],$job['issues']);
            }else{$store->saveJob($row,$job,'failed');throw new DomainException(in_array($error->getMessage(),['capacity_unknown','capacity_changed'],true)?$error->getMessage():'generation_provider');}
        }
    }
    /** Import a reviewed snapshot as an inactive MCQ pool in the active course. */
    public function importCourse($id,$context)
    {
        $row=$this->read($id);$user=$this->getObject('user','security');
        $course=$this->getObject('dbcontext','context')->getContextDetails($context);
        if(!$course || $context==='' || $context==='root' || !($user->isAdmin() || $user->isCourseAdmin($context) || $user->isContextLecturer($user->userId(),$context)))throw new DomainException('course_forbidden');
        return $this->getObject('workshopstore')->importOnce($id,$context,function($current)use($context,$user){
            if(empty($current['reviewed']))throw new DomainException('review_required');
            $questions=$this->selected(json_decode($current['questions_json'],true));
            // MCQ stores rich HTML: plain-text authoring must remain inert there.
            foreach($questions as &$q){$encode=static fn($v)=>preg_replace_callback('/[\x{10000}-\x{10FFFF}]/u',static fn($m)=>'&#'.mb_ord($m[0],'UTF-8').';',htmlspecialchars($v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8'));$q['stem']=$encode($q['stem']);$q['options']=array_map($encode,$q['options']);}
            unset($q);
            $tests=$this->getObject('dbtestadmin','mcqtests');
            $test=$tests->addTest(['context'=>$context,'chapter'=>'','userid'=>(string)$user->userId(),'name'=>mb_substr($current['title'],0,60),'description'=>'','status'=>'inactive','totalmark'=>0,'percentage'=>0,'duration'=>0,'timed'=>0,'testtype'=>'Formative','qsequence'=>'Sequential','asequence'=>'Scrambled','comlab'=>'','updated'=>$this->getObject('timeanddateservice','timeanddate-service')->nowStorage(),'coursePermissions'=>'Private']);
            if(!$test || !$tests->getRow('id',$test))throw new RuntimeException('storage_failed');
            $result=$this->getObject('mcqaigenerator','mcqtests')->insertQuestions($test,$questions);
            if(empty($result['ok']))throw new RuntimeException('storage_failed');
            $saved=$this->getObject('dbquestions','mcqtests')->getQuestions($test);
            if(!is_array($saved)||count($saved)!==count($questions))throw new RuntimeException('storage_failed');
            $answers=$this->getObject('dbanswers','mcqtests');
            foreach($saved as $question){
                $rows=$answers->getAll("WHERE questionid=".$this->objEngine->getDbObj()->quote($question['id'],'text'));
                if(!is_array($rows)||count($rows)!==4||array_sum(array_column($rows,'correct'))!==1)throw new RuntimeException('storage_failed');
            }
            return $test;
        });
    }
    /** Retain bounded editable candidates, with flagged questions excluded initially. */
    public function candidates(array $questions,array $issues)
    {
        $flagged=[];foreach($issues as $issue)if(isset($issue['question']))$flagged[(int)$issue['question']]=true;
        $text=static fn($v)=>is_string($v)?mb_substr(preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u','',$v),0,4000,'UTF-8'):'';
        $rows=[];
        foreach(array_values(array_slice($questions,0,30)) as $i=>$q){
            $q=is_array($q)?$q:[];$options=is_array($q['options']??null)?array_values($q['options']):[];
            $rows[]=['stem'=>$text($q['stem']??''),'options'=>array_map($text,array_slice(array_pad($options,4,''),0,4)),
                'correctIndex'=>is_scalar($q['correctIndex']??null)&&preg_match('/^[0-3]$/D',(string)$q['correctIndex'])?(int)$q['correctIndex']:0,
                'sourceBasis'=>$text($q['sourceBasis']??''),'included'=>!isset($flagged[$i+1])];
        }
        return $rows;
    }
    public function review($questions,$expected)
    {
        if(!is_array($questions)||count($questions)!==$expected||$expected<1||$expected>30)throw new DomainException('questions_invalid');
        $rows=$this->candidates($questions,[]);
        foreach(array_values($questions) as $i=>$q){
            $included=is_array($q)&&in_array($q['included']??null,[true,1,'1'],true);
            if($included)$rows[$i]=$this->validate([$q],1)[0];
            $rows[$i]['included']=$included;
        }
        return $rows;
    }
    public function selected($questions)
    {
        if(!is_array($questions))throw new DomainException('questions_invalid');
        $selected=array_values(array_filter($questions,static fn($q)=>is_array($q)&&($q['included']??true)));
        if(!$selected)throw new DomainException('none_included');
        return $this->validate($selected,count($selected));
    }
    public function validate($questions,$count=5)
    {
        if(!is_array($questions)||count($questions)!==$count || $count<1 || $count>30)throw new DomainException('questions_invalid');
        $clean=[];
        foreach($questions as $q){
            if(!is_array($q)||!isset($q['stem'],$q['options'],$q['correctIndex'],$q['sourceBasis'])||!is_array($q['options'])||count($q['options'])!==4||!is_scalar($q['correctIndex'])||!preg_match('/^[0-3]$/D',(string)$q['correctIndex']))throw new DomainException('questions_invalid');
            foreach(array_merge([$q['stem'],$q['sourceBasis']],$q['options']) as $text){
                if(!is_string($text)||trim($text)===''||mb_strlen($text)>4000||!mb_check_encoding($text,'UTF-8')||preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F]/',$text))throw new DomainException('questions_invalid');
            }
            $options=array_map('trim',array_values($q['options']));
            if(count(array_unique($options))!==4)throw new DomainException('questions_invalid');
            $clean[]=['stem'=>trim($q['stem']),'options'=>$options,'correctIndex'=>(int)$q['correctIndex'],'sourceBasis'=>trim($q['sourceBasis'])];
        }
        return $clean;
    }
}
