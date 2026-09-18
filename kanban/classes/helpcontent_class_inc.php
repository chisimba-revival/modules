<?php
/** Recovery guidance through the shared contextual Help service. */
class helpcontent extends ChisimbaObject
{
    public function mayViewTopic($topicId) { return $topicId === 'recovery' && $this->getObject('user', 'security')->isLoggedIn(); }
    public function getTopic($topicId) {
        if (!$this->mayViewTopic($topicId)) return null;
        $language = $this->getObject('language', 'language');
        $text = fn($key) => $language->languageText('mod_kanban_help_'.$key, 'kanban');
        return array('title'=>$text('title'), 'summary'=>$text('summary'), 'steps'=>array_map(fn($i)=>$text('step_'.$i),range(1,6)), 'sections'=>array());
    }
}
