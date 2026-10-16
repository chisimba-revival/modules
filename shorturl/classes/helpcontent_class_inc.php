<?php
if (empty($GLOBALS['kewl_entry_point_run'])) die('You cannot view this page directly');
class helpcontent extends ChisimbaObject
{
    public function init() {}
    public function mayViewTopic($topicId)
    {
        $user=$this->getObject('user','security');
        return $topicId==='editing' && $user->isLoggedIn() && $user->lookupAdmin($user->userId());
    }
    public function getTopic($topicId)
    {
        if ($topicId!=='editing') return null;
        $language=$this->getObject('language','language');
        $text=fn($key)=>html_entity_decode($language->languageText('mod_shorturl_help_'.$key,'shorturl'),ENT_QUOTES,'UTF-8');
        return ['title'=>$text('title'),'summary'=>$text('summary'),'steps'=>array_map($text,['edit','save','recover','delete','dynamic'])];
    }
}
