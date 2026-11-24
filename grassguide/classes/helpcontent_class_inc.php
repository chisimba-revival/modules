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
        return ['title'=>$r->text('title'),'summary'=>$r->text('help_summary'),'steps'=>array_map(fn($key)=>$r->text($key),['help_setup','help_membership','help_species','help_media','help_recovery','help_export','help_rights','help_contributors'])];
    }
}
