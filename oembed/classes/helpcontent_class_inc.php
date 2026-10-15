<?php
/** Read-only help for native media consumption. @author Derek Keats */
if (empty($GLOBALS['kewl_entry_point_run'])) die('No direct access');
class helpcontent extends ChisimbaObject
{
    public function init() {}
    public function mayViewTopic($topic) { return $topic === 'media-previews'; }
    public function getTopic($topic)
    {
        if (!$this->mayViewTopic($topic)) return null;
        $defaults = [
            'title'=>'Media previews',
            'summary'=>'Preview supported media while keeping its original link.',
            'show'=>'Choose Show media to load an image, audio, video or supported external player. Loading a preview connects your browser to that media provider.',
            'fallback'=>'The original link remains available. Unsupported URLs or a failed preview can be opened directly at their source.',
            'permissions'=>'Previews do not grant access to private files. Access remains controlled by the source site. Nothing is saved or published by opening a preview.'
        ];
        $language = $this->getObject('language','language');
        $text = static fn($key) => $language->languageText('mod_oembed_help_'.$key,'oembed',$defaults[$key]);
        return ['title'=>$text('title'),'summary'=>$text('summary'),'steps'=>[$text('show'),$text('fallback'),$text('permissions')],'sections'=>[]];
    }
}
