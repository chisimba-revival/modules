<?php
/** Contextual help for practice and authoring. @author Derek Keats */
if (empty($GLOBALS['kewl_entry_point_run'])) die('No direct access');
class helpcontent extends ChisimbaObject
{
    public function mayViewTopic($topicId)
    {
        try {
            $access=$this->getObject('practiceaccess','sightsandsounds'); $access->scope();
            return $topicId==='practice' || ($topicId==='authoring' && $access->manager());
        } catch (DomainException $e) { return false; }
    }
    public function getTopic($topicId)
    {
        if (!$this->mayViewTopic($topicId)) return null;
        $language=$this->getObject('language','language');
        $text=fn($key)=>html_entity_decode($language->code2Txt('mod_sightsandsounds_help_'.$key,'sightsandsounds'),ENT_QUOTES,'UTF-8');
        return ['title'=>$text($topicId.'_title'),'summary'=>$text($topicId.'_summary'),
            'steps'=>array_map($text,[$topicId.'_one',$topicId.'_two',$topicId.'_three','recovery'])];
    }
}
