<?php
/** Assemble exams without changing source sets or invoking AI. @author Derek Keats */
if(empty($GLOBALS['kewl_entry_point_run']))die('No direct access');
class examservice extends ChisimbaObject
{
    const MAX_QUESTIONS=200;
    public function read($id)
    {
        $row=$this->getObject('examstore')->one($id);
        if(!$row||!$this->getObject('workshoppolicy')->owner($row))throw new DomainException('not_found');
        return $row;
    }
    public function emptyContent(){return ['instructions'=>'','headings'=>true,'reviewed'=>false,'questions'=>[]];}
    public function content(array $row)
    {
        $content=json_decode($row['content_json'],true,512,JSON_THROW_ON_ERROR);
        $original=$content['questions'];
        $content=$this->structure($content);
        if($original!==$content['questions'])$content['reviewed']=false;
        return $content;
    }
    public function type(array $entry){return ($entry['question']['type']??'mcq')==='short_answer'?'short_answer':'mcq';}
    public function marks(array $entry){return $this->type($entry)==='short_answer'?2:1;}
    /** Stable partition: retain question IDs, answers and source references. */
    public function structure(array $content)
    {
        $groups=['mcq'=>[],'short_answer'=>[]];
        foreach($content['questions'] as $entry){$entry['marks']=$this->marks($entry);$groups[$this->type($entry)][]=$entry;}
        $content['questions']=array_merge($groups['mcq'],$groups['short_answer']);
        return $content;
    }
    public function sections(array $content)
    {
        $sections=['mcq'=>['title'=>'exam_section_mcq','marks'=>1,'questions'=>[]],
            'short_answer'=>['title'=>'exam_section_short','marks'=>2,'questions'=>[]]];
        foreach($content['questions'] as $entry)$sections[$this->type($entry)]['questions'][]=$entry;
        return $sections;
    }
    /** Source rubrics are retained in storage; old per-point scores do not set exam marks. */
    public function markingCriteria(array $question)
    {
        $text=$question['markingPoints'];
        $text=preg_replace('/\s*[(:–—-]?\s*\d+(?:\.\d+)?\s*marks?\)?(?=\s*(?:$|\n|;))/iu','',$text);
        return preg_replace('/^\s*\d+(?:\.\d+)?\s*marks?\s*[:–—-]\s*/imu','',$text);
    }
    public function previewQuestion(array $question)
    {
        if(($question['type']??'mcq')==='short_answer'){
            $question['marks']=2;
            $question['markingPoints']=$this->getObject('workshoprenderer')->text('exam_two_mark_rubric')."\n".$this->markingCriteria($question);
        }
        return $question;
    }
    public function revision(array $row,$version){if((string)$row['version']!==(string)$version)throw new DomainException('exam_changed');}
    public function add(array $row,array $set,$indices)
    {
        if(!$this->getObject('workshoppolicy')->owner($set))throw new DomainException('not_found');
        if(($set['examid']??'')!==$row['id'])throw new DomainException('exam_wrong_chapter');
        if(!is_array($indices)||!$indices||count($indices)>30)throw new DomainException('exam_selection');
        $content=$this->content($row);$source=json_decode($set['questions_json'],true,512,JSON_THROW_ON_ERROR);$existing=[];
        foreach($content['questions'] as $q)$existing[$q['sourceSetId'].':'.$q['sourceIndex']]=true;
        $added=0;
        foreach(array_unique($indices,SORT_REGULAR) as $index){
            if(!is_scalar($index)||!preg_match('/^(?:0|[1-9][0-9]?)$/D',(string)$index)||!isset($source[(int)$index]))throw new DomainException('exam_selection');
            $index=(int)$index;$key=$set['id'].':'.$index;
            if(isset($existing[$key]))continue;
            $q=$source[$index];if(!($q['included']??true))throw new DomainException('exam_selection');
            $q=$this->getObject('workshopservice')->validate([$q],1)[0];
            $content['questions'][]=['id'=>bin2hex(random_bytes(12)),'sourceSetId'=>$set['id'],'sourceVersion'=>(int)$set['version'],'sourceIndex'=>$index,'chapter'=>$set['title'],'marks'=>($q['type']??'mcq')==='short_answer'?2:1,'question'=>$q];
            $existing[$key]=true;++$added;
        }
        if(!$added)throw new DomainException('exam_selection');
        if(count($content['questions'])>self::MAX_QUESTIONS)throw new DomainException('exam_limit');
        $content=$this->mixAnswers($content,count($content['questions'])-$added);
        $content['reviewed']=false;return $this->structure($content);
    }
    public function edit(array $row,$input)
    {
        if(!is_array($input)||($input['complete']??'')!=='1')throw new DomainException('exam_invalid');
        $content=$this->content($row);$instructions=$input['instructions']??'';
        if(!is_string($instructions)||!mb_check_encoding($instructions,'UTF-8')||mb_strlen($instructions)>4000||preg_match('/[\x00-\x08\x0b\x0c\x0e-\x1f]/',$instructions))throw new DomainException('exam_invalid');
        $entries=$input['entries']??[];
        if(!is_array($entries)||count($entries)!==count($content['questions']))throw new DomainException('exam_invalid');
        $ordered=[];
        foreach($content['questions'] as $i=>$q){
            $entry=$entries[$q['id']]??null;if(!is_array($entry))throw new DomainException('exam_invalid');
            if(($entry['keep']??'')!=='1')continue;
            $order=$entry['order']??'';
            if(!is_scalar($order)||!preg_match('/^[1-9][0-9]{0,3}$/D',(string)$order))throw new DomainException('exam_invalid');
            $q['marks']=$this->marks($q);$ordered[]=['order'=>(int)$order,'index'=>$i,'entry'=>$q];
        }
        usort($ordered,static fn($a,$b)=>[$a['order'],$a['index']]<=>[$b['order'],$b['index']]);
        $content['questions']=array_column($ordered,'entry');$content['instructions']=trim($instructions);
        $content['headings']=($input['headings']??'')==='1';$content['reviewed']=($input['reviewed']??'')==='1';
        if(($input['mixAnswers']??'')==='1'){$content=$this->mixAnswers($content);$content['reviewed']=false;}
        return $this->structure($content);
    }
    /** Persist option order with its answer index; never shuffle during export. */
    public function mixAnswers(array $content,$start=0)
    {
        $counts=[0,0,0,0];
        foreach($content['questions'] as $i=>&$entry){
            $q=&$entry['question'];
            if(($q['type']??'mcq')==='short_answer'){unset($q);continue;}
            if($i<$start){++$counts[$q['correctIndex']];continue;}
            $positions=array_keys($counts,min($counts),true);
            $target=$positions[random_int(0,count($positions)-1)];
            $correct=$q['options'][$q['correctIndex']];
            $others=$q['options'];array_splice($others,$q['correctIndex'],1);
            shuffle($others);array_splice($others,$target,0,[$correct]);
            $q['options']=$others;$q['correctIndex']=$target;++$counts[$target];
            unset($q);
        }
        unset($entry);return $content;
    }
    public function lines(array $row,$answers=false,?array &$styles=null)
    {
        $styles=[0=>'Title'];$content=$this->content($row);$r=$this->getObject('workshoprenderer');$questions=$content['questions'];
        if(!$questions)throw new DomainException('exam_empty');
        $lines=[$row['title'],$r->text($answers?'exam_marking':'question_paper'),$r->text('exam_total').': '.array_sum(array_column($questions,'marks'))];
        if(!$content['reviewed'])$lines[]=$r->text('draft_notice');
        if(!$answers&&$content['instructions']!=='')$lines=array_merge($lines,[''],preg_split('/\R/u',$content['instructions']));
        $chapter=null;$section=null;
        foreach($questions as $i=>$entry){
            $type=$this->type($entry);
            if($section!==$type){$section=$type;$chapter=null;$lines[]='';$styles[count($lines)]='Section';$lines[]=$r->text($type==='mcq'?'exam_section_mcq':'exam_section_short');}

            $q=$entry['question'];
            if(($content['headings']||$answers)&&$chapter!==$entry['chapter']){$chapter=$entry['chapter'];$lines[]='';$styles[count($lines)]='Heading';$lines[]=$chapter;$styles[count($lines)]='Keep';}
            $lines[]='';$styles[count($lines)]='Keep';$lines[]=($i+1).'. '.$q['stem'].' ['.$entry['marks'].' '.lcfirst($r->text($entry['marks']===1?'exam_mark':'exam_marks')).']';
            if(($q['type']??'mcq')==='short_answer'){
                if($answers){$lines[]=$r->text('model_answer').': '.$q['modelAnswer'];$lines[]=$r->text('exam_two_mark_rubric');$lines[]=$r->text('marking_points').': '.$this->markingCriteria($q);$lines[]=$r->text('exam_source').': '.$entry['chapter'].' / '.($entry['sourceIndex']+1);$lines[]=$r->text('source_basis').': '.$q['sourceBasis'];}
                else {$lines[]='';$lines[]='';$lines[]='';}
                continue;
            }
            if($answers){$styles[count($lines)]='Keep';$lines[]=chr(65+$q['correctIndex']).'. '.$q['options'][$q['correctIndex']];$styles[count($lines)]='Keep';$lines[]=$r->text('exam_source').': '.$entry['chapter'].' / '.($entry['sourceIndex']+1);$lines[]=$r->text('source_basis').': '.$q['sourceBasis'];}
            else foreach($q['options'] as $j=>$option){if($j<3)$styles[count($lines)]='Keep';$lines[]=chr(65+$j).'. '.$option;}
        }
        return $lines;
    }
    public function odt(array $row,$answers=false)
    {
        $styles=[];$lines=$this->lines($row,$answers,$styles);
        return $this->getObject('workshopexport')->odtLines($lines,$styles);
    }
}
