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
    public function content(array $row){return json_decode($row['content_json'],true,512,JSON_THROW_ON_ERROR);}
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
            $content['questions'][]=['id'=>bin2hex(random_bytes(12)),'sourceSetId'=>$set['id'],'sourceVersion'=>(int)$set['version'],'sourceIndex'=>$index,'chapter'=>$set['title'],'marks'=>1,'question'=>$q];
            $existing[$key]=true;++$added;
        }
        if(!$added)throw new DomainException('exam_selection');
        if(count($content['questions'])>self::MAX_QUESTIONS)throw new DomainException('exam_limit');
        $content=$this->mixAnswers($content,count($content['questions'])-$added);
        $content['reviewed']=false;return $content;
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
            $order=$entry['order']??'';$marks=$entry['marks']??'';
            if(!is_scalar($order)||!preg_match('/^[1-9][0-9]{0,3}$/D',(string)$order)||!is_scalar($marks)||!preg_match('/^(?:[1-9]|[1-9][0-9]|100)$/D',(string)$marks))throw new DomainException('exam_invalid');
            $q['marks']=(int)$marks;$ordered[]=['order'=>(int)$order,'index'=>$i,'entry'=>$q];
        }
        usort($ordered,static fn($a,$b)=>[$a['order'],$a['index']]<=>[$b['order'],$b['index']]);
        $content['questions']=array_column($ordered,'entry');$content['instructions']=trim($instructions);
        $content['headings']=($input['headings']??'')==='1';$content['reviewed']=($input['reviewed']??'')==='1';
        if(($input['mixAnswers']??'')==='1'){$content=$this->mixAnswers($content);$content['reviewed']=false;}
        return $content;
    }
    /** Persist option order with its answer index; never shuffle during export. */
    public function mixAnswers(array $content,$start=0)
    {
        $counts=[0,0,0,0];
        foreach($content['questions'] as $i=>&$entry){
            $q=&$entry['question'];
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
        $chapter=null;
        foreach($questions as $i=>$entry){
            $q=$entry['question'];
            if(($content['headings']||$answers)&&$chapter!==$entry['chapter']){$chapter=$entry['chapter'];$lines[]='';$styles[count($lines)]='Heading';$lines[]=$chapter;$styles[count($lines)]='Keep';}
            $lines[]='';$styles[count($lines)]='Keep';$lines[]=($i+1).'. '.$q['stem'].' ['.$entry['marks'].' '.lcfirst($r->text($entry['marks']===1?'exam_mark':'exam_marks')).']';
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
