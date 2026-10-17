<?php
/** Guidance for native Discussion editing. @author Derek Keats */
if (empty($GLOBALS['kewl_entry_point_run'])) die('No direct access');
class helpcontent extends ChisimbaObject {
    public function mayViewTopic($topic) { return $topic === 'editpost' && $this->getObject('user', 'security')->isLoggedIn(); }
    public function getTopic($topic) {
        if (!$this->mayViewTopic($topic)) return null;
        $t = fn($key) => $this->getObject('language', 'language')->languageText($key, 'discussion');
        return array('title'=>$t('mod_discussion_editposttitle'), 'summary'=>$t('mod_discussion_edit_help'), 'steps'=>array($t('mod_discussion_edit_recovery')));
    }
}
