<?php
if (empty($GLOBALS['kewl_entry_point_run'])) die('No direct access');
class helpcontent extends ChisimbaObject
{
    public function init() {}
    public function mayViewTopic($id) { return $id==='administration' && $this->getObject('grassguideservice','grassguide')->allowed(); }
    public function getTopic($id)
    {
        if (!$this->mayViewTopic($id)) return null;
        $r=$this->getObject('grassguiderenderer','grassguide');
        return ['title'=>$r->text('title'),'summary'=>$r->text('help_summary'),'steps'=>array_map(fn($key)=>$r->text($key),['help_setup','help_membership','help_species','italic_help','help_media','audience_help','help_image_review','batch_remove_help','help_photos','copyright_help','help_recovery','help_export','help_rights','help_contributors','help_contact_mail','help_premium'])];
    }
}
