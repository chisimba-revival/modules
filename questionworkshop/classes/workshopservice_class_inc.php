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
            if(empty($result['ok']))throw new DomainException('generation_failed');
            $questions=$this->validate($result['questions'],(int)$row['question_count']);
            $store->finish($row,$questions);
        }catch(Throwable $e){$store->failed($row);throw new DomainException('generation_failed');}
    }
    /** Import a reviewed snapshot as an inactive MCQ pool in the active course. */
    public function importCourse($id,$context)
    {
        $row=$this->read($id);$user=$this->getObject('user','security');
        $course=$this->getObject('dbcontext','context')->getContextDetails($context);
        if(!$course || $context==='' || $context==='root' || !($user->isAdmin() || $user->isCourseAdmin($context) || $user->isContextLecturer($user->userId(),$context)))throw new DomainException('course_forbidden');
        return $this->getObject('workshopstore')->importOnce($id,$context,function($current)use($context,$user){
            if(empty($current['reviewed']))throw new DomainException('review_required');
            $questions=$this->validate(json_decode($current['questions_json'],true),(int)$current['question_count']);
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
