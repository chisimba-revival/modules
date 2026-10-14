<?php
/** Activity browsing guidance. @author Derek Keats */
if(empty($GLOBALS['kewl_entry_point_run']))die('No direct access');
class helpcontent extends ChisimbaObject
{
    public function mayViewTopic($topic)
    {
        $browser=$this->getObject('activitybrowser','activitystreamer');
        return $topic==='browse' && ($browser->mayView('site')||$browser->mayView('pages'));
    }
    public function getTopic($topic)
    {
        if(!$this->mayViewTopic($topic))return null;
        $view=$this->getObject('activityview','activitystreamer');
        return ['title'=>$view->text('help'),'summary'=>$view->text('intro'),'steps'=>[$view->text('helpscope'),$view->text('helpsearch'),$view->text('helppages'),$view->text('helprecording')],'sections'=>[]];
    }
}
