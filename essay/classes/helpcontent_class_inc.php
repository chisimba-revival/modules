<?php
/** Contextual help topics owned by Essays. @package essay */
class helpcontent extends ChisimbaObject
{
    public function init()
    {
        $this->language = $this->getObject('language', 'language');
        $this->user = $this->getObject('user', 'security');
        $this->context = $this->getObject('dbcontext', 'context');
    }

    public function mayViewTopic($topicId)
    {
        if ($topicId !== 'ai-assisted-marking' || !$this->user->isLoggedIn()) {
            return false;
        }
        if ($this->user->isAdmin()) { return true; }
        $contextCode = $this->context->getContextCode();
        return $contextCode !== '' && $this->user->isCourseAdmin($contextCode);
    }

    public function getTopic($topicId)
    {
        if ($topicId !== 'ai-assisted-marking') { return null; }
        $text = function ($code) {
            return $this->language->code2Txt($code, 'essay');
        };
        return array(
            'title' => $text('mod_essay_help_ai_title'),
            'summary' => $text('mod_essay_help_ai_summary'),
            'steps' => array(
                $text('mod_essay_help_ai_step_review'),
                $text('mod_essay_help_ai_step_suggest'),
                $text('mod_essay_help_ai_step_check'),
                $text('mod_essay_help_ai_step_adjust'),
                $text('mod_essay_help_ai_step_feedback'),
                $text('mod_essay_help_ai_step_save'),
            ),
            'sections' => array(
                array('heading' => $text('mod_essay_help_ai_draft_heading'), 'body' => $text('mod_essay_help_ai_draft_body')),
                array('heading' => $text('mod_essay_help_ai_evidence_heading'), 'body' => $text('mod_essay_help_ai_evidence_body')),
                array('heading' => $text('mod_essay_help_ai_authorship_heading'), 'body' => $text('mod_essay_help_ai_authorship_body')),
                array('heading' => $text('mod_essay_help_ai_final_heading'), 'body' => $text('mod_essay_help_ai_final_body')),
            ),
        );
    }
}
?>
