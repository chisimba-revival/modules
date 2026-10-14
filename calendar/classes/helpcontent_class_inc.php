<?php
/** Calendar viewing and editing guides. */
if(empty($GLOBALS['kewl_entry_point_run']))die('You cannot view this page directly');
class helpcontent extends ChisimbaObject
{
    public function init(){}
    public function mayViewTopic($topic){return in_array($topic,['viewing-events','editing-events'],true) && $this->getObject('user','security')->isLoggedIn();}
    public function getTopic($topic){
        if($topic==='editing-events'){
            $language=$this->getObject('language','language');$text=static fn($key)=>html_entity_decode($language->languageText('mod_calendar_help_edit_'.$key,'calendar'),ENT_QUOTES,'UTF-8');
            return ['title'=>$text('title'),'summary'=>$text('summary'),'steps'=>[$text('scope'),$text('dates'),$text('save'),$text('recovery'),$text('attachments')],'sections'=>[]];
        }
        if($topic!=='viewing-events')return null;
        $language=$this->getObject('language','language');
        $text=static fn($key)=>html_entity_decode($language->languageText('mod_calendar_help_view_'.$key,'calendar'),ENT_QUOTES,'UTF-8');
        return ['title'=>$text('title'),'summary'=>$text('summary'),'steps'=>[$text('filter'),$text('details'),$text('month')],'sections'=>[]];
    }
}
