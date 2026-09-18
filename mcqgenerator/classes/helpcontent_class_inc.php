<?php
/** Contextual help uses the shared accessible drawer. @author Derek Keats */
if(empty($GLOBALS['kewl_entry_point_run']))die('No direct access');
class helpcontent extends ChisimbaObject
{
    public function mayViewTopic($topic){return in_array($topic,['guide','exams'],true)&&$this->getObject('workshoppolicy')->allowed();}
    public function getTopic($topic){
        if(!$this->mayViewTopic($topic))return null;
        $r=$this->getObject('workshoprenderer');$steps=[];$sections=[];
        if($topic==='exams')return ['title'=>$r->text('help_exams_title'),'summary'=>$r->text('help_exams'),'steps'=>[$r->text('help_exam_select'),$r->text('help_exam_arrange'),$r->text('help_exam_download')],'sections'=>[['heading'=>$r->text('help_course_title'),'body'=>$r->text('help_exam_bank')]]];
        foreach(['source','generate','review','download'] as $key)$steps[]=$r->text('help_'.$key);
        foreach(['more','longchapters','privacy','recovery','course'] as $key)$sections[]=['heading'=>$r->text('help_'.$key.'_title'),'body'=>$r->text('help_'.$key)];
        return ['title'=>$r->text('help_title'),'summary'=>$r->text('help_summary'),'steps'=>$steps,'sections'=>$sections];
    }
}
