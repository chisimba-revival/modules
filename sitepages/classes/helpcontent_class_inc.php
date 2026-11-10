<?php
if(empty($GLOBALS['kewl_entry_point_run']))die('No direct access');
class helpcontent extends ChisimbaObject
{
    public function init(){}
    public function mayViewTopic($id)
    { return $id==='pages' && $this->getObject('pagepolicy','sitepages')->canManage(); }
    public function getTopic($id)
    {
        if($id!=='pages')return null;
        $blocks=$this->getObject('compositionservice','contentblocks');
        $language=$this->getObject('language','language');
        $text=fn($key)=>$language->languageText('mod_sitepages_help_'.$key,'sitepages');
        return ['title'=>$text('title'),'summary'=>$text('summary'),
            'steps'=>[$text('steps'),$text('edit'),$text('save')],
            'sections'=>[
                ['heading'=>$text('settings'),'body'=>$text('recovery')],
                ['heading'=>$text('imported_title'),'body'=>$text('imported')],
                ['heading'=>$blocks->text('audio'),'body'=>$blocks->text('audio_help')],
                ['heading'=>$blocks->text('video_gallery'),'body'=>$blocks->text('gallery_help')],
            ]];
    }
}
