<?php
/** Administrator-only maintenance help, rendered by the canonical Help service.
 * @author Derek Keats
 * @package systemmanagement
 */
class helpcontent extends ChisimbaObject
{
    private $language;
    private $user;
    private const TOPICS = array('maintenance' => array('prefix' => 'maintenance', 'steps' => 6, 'sections' => 6), 'deployment-runbook' => array('prefix' => 'deployment_runbook', 'steps' => 7, 'sections' => 9));

    public function init()
    {
        $this->language = $this->getObject('language', 'language');
        $this->user = $this->getObject('user', 'security');
    }

    public function mayViewTopic($topicId)
    {
        return isset(self::TOPICS[$topicId]) && $this->user->isAdmin();
    }

    public function getTopic($topicId)
    {
        if (!$this->mayViewTopic($topicId)) { return null; }
        $definition = self::TOPICS[$topicId];
        $text = fn($key) => $this->language->code2Txt(
            'mod_systemmanagement_help_' . $definition['prefix'] . '_' . $key,
            'systemmanagement'
        );
        $topic = array('title' => $text('title'), 'summary' => $text('summary'),
            'steps' => array(), 'sections' => array());
        for ($i = 1; $i <= $definition['steps']; $i++) {
            $topic['steps'][] = $text('step_' . $i);
        }
        for ($i = 1; $i <= $definition['sections']; $i++) {
            $topic['sections'][] = array('heading' => $text('heading_' . $i), 'body' => $text('body_' . $i));
        }
        return $topic;
    }
}
?>
