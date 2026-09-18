<?php
/** Contextual help uses the shared accessible drawer. @author Derek Keats */
if(empty($GLOBALS['kewl_entry_point_run']))die('No direct access');
class helpcontent extends ChisimbaObject
{
    public function mayViewTopic($topic){return $topic==='guide'&&$this->getObject('workshoppolicy')->allowed();}
    public function getTopic($topic){
        if(!$this->mayViewTopic($topic))return null;
        $r=$this->getObject('workshoprenderer');$steps=[];$sections=[];
        foreach(['source','generate','review','download'] as $key)$steps[]=$r->text('help_'.$key);
        foreach(['longchapters','privacy','recovery','course'] as $key)$sections[]=['heading'=>$r->text('help_'.$key.'_title'),'body'=>$r->text('help_'.$key)];
        return ['title'=>$r->text('help_title'),'summary'=>$r->text('help_summary'),'steps'=>$steps,'sections'=>$sections];
    }
}
