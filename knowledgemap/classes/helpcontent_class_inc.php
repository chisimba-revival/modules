<?php
/** Contextual guidance for semantic course blueprints. @author Derek Keats */
if (empty($GLOBALS['kewl_entry_point_run'])) die('No direct access');
class helpcontent extends ChisimbaObject
{
    public function mayViewTopic($topic) { return $topic==='course' && $this->getObject('user','security')->isLoggedIn(); }
    public function getTopic($topic)
    {
        if (!$this->mayViewTopic($topic)) return null;
        $t=fn($key)=>ucfirst($this->getObject('language','language')->code2Txt('mod_knowledgemap_course_'.$key,'knowledgemap'));
        return ['title'=>$t('blueprint'),'summary'=>$t('help_summary'),
            'steps'=>[$t('role_help'),$t('assign_pages_help'),$t('help_text'),$t('help_preview'),$t('help_create'),$t('quiz_help')],
            'sections'=>[['heading'=>$t('help_protection_title'),'body'=>$t('protection')],
                ['heading'=>$t('help_recovery_title'),'body'=>$t('help_recovery')]]];
    }
}
