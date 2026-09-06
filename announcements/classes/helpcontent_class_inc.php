<?php
/** Contextual Help topics owned by Announcements. @package announcements */
class helpcontent extends ChisimbaObject
{
    public function init()
    {
        $this->language = $this->getObject('language', 'language');
        $this->user = $this->getObject('user', 'security');
        $this->contexts = $this->getObject('usercontext', 'context');
    }

    public function mayViewTopic($topicId)
    {
        if ($topicId !== 'publishing-an-announcement' || !$this->user->isLoggedIn()) {
            return false;
        }
        return $this->user->isAdmin()
            || count((array) $this->contexts->getContextWhereLecturer($this->user->userId())) > 0;
    }

    public function getTopic($topicId)
    {
        if ($topicId !== 'publishing-an-announcement') { return null; }
        $text = function ($code) {
            return html_entity_decode(
                $this->language->code2Txt($code, 'announcements'),
                ENT_QUOTES | ENT_HTML5,
                'UTF-8'
            );
        };
        return array(
            'title' => $text('mod_announcements_help_publish_title'),
            'summary' => $text('mod_announcements_help_publish_summary'),
            'steps' => array(
                $text($this->user->isAdmin()
                    ? 'mod_announcements_help_publish_step_type'
                    : 'mod_announcements_help_publish_step_type_context'),
                $text('mod_announcements_help_publish_step_audience'),
                $text('mod_announcements_help_publish_step_scope'),
                $text('mod_announcements_help_publish_step_delivery'),
            ),
            'sections' => array(
                array('heading' => $text('mod_announcements_help_type_heading'), 'body' => $text('mod_announcements_help_type_body')),
                array('heading' => $text('mod_announcements_help_audience_heading'), 'body' => $text('mod_announcements_help_audience_body')),
                array('heading' => $text('mod_announcements_help_scope_heading'), 'body' => $text('mod_announcements_help_scope_body')),
                array('heading' => $text('mod_announcements_help_delivery_heading'), 'body' => $text('mod_announcements_help_delivery_body')),
                array('heading' => $text('mod_announcements_help_sidebar_heading'), 'body' => $text('mod_announcements_help_sidebar_body')),
            ),
        );
    }
}
?>
