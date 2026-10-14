<?php
/** Personal Space editing guidance. @author Derek Keats */
if (empty($GLOBALS['kewl_entry_point_run'])) die('You cannot view this page directly');
class helpcontent extends ChisimbaObject
{
    public function init() {}
    public function mayViewTopic($topicId)
    {
        return $topicId === 'editing' && $this->getObject('user','security')->isLoggedIn();
    }
    public function getTopic($topicId)
    {
        if ($topicId !== 'editing') return null;
        $language=$this->getObject('language','language');
        $text=fn($key)=>html_entity_decode($language->languageText('mod_personalspace_help_'.$key,'personalspace'), ENT_QUOTES, 'UTF-8');
        return ['title'=>$text('title'), 'summary'=>$text('summary'),
            'steps'=>array_map($text, ['edit','add','move','remove','recover'])];
    }
}
