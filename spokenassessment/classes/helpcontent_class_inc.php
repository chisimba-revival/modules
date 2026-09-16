<?php
/** Permission-aware contextual help for spoken practice. @author Derek Keats */
if (empty($GLOBALS['kewl_entry_point_run'])) die('No direct access');
class helpcontent extends ChisimbaObject
{
    public function mayViewTopic($topic)
    {
        return $topic==='practice' && $this->getObject('spokenpolicy')->member(
            (string)$this->getObject('user','security')->userId(),(string)$this->getObject('dbcontext','context')->getContextCode());
    }
    public function getTopic($topic)
    {
        if (!$this->mayViewTopic($topic)) return null;
        $r=$this->getObject('spokenrenderer');
        $sections=[];
        foreach (['recording','corrections','recovery','privacy','availability'] as $key) {
            $sections[]=['heading'=>$r->text('help_heading_'.$key),'body'=>$r->text('help_'.$key)];
        }
        $userId=(string)$this->getObject('user','security')->userId();
        $context=(string)$this->getObject('dbcontext','context')->getContextCode();
        if ($this->getObject('spokenpolicy')->teacher($userId,$context)) {
            foreach (['create','review','snapshots'] as $key) {
                $sections[]=['heading'=>$r->text('help_heading_'.$key),'body'=>$r->text('help_'.$key)];
            }
        }
        return ['title'=>$r->text('help_title'),'summary'=>$r->text('formative'),
            'steps'=>[$r->text('help_speak'),$r->text('help_transcript'),$r->text('help_feedback'),$r->text('help_retry')],
            'sections'=>$sections];
    }
}
