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
        if (!$this->user->isLoggedIn()) {
            return false;
        }
        $contextCode = $this->context->getContextCode();
        if ($topicId === 'submitting-an-essay') {
            return $contextCode !== '' && $this->user->isContextStudent($contextCode);
        }
        if ($topicId !== 'ai-assisted-marking') { return false; }
        if ($this->user->isAdmin()) { return true; }
        return $contextCode !== '' && $this->user->isCourseAdmin($contextCode);
    }

    public function getTopic($topicId)
    {
        $text = function ($code) {
            return $this->language->code2Txt($code, 'essay');
        };
        if ($topicId === 'submitting-an-essay') {
            return array(
                'title' => $text('mod_essay_help_submit_title'),
                'summary' => $text('mod_essay_help_submit_summary'),
                'steps' => array(
                    $text('mod_essay_help_submit_step_choose'),
                    $text('mod_essay_help_submit_step_draft'),
                    $text('mod_essay_help_submit_step_review'),
                    $text('mod_essay_help_submit_step_submit'),
                    $text('mod_essay_help_submit_step_return'),
                ),
                'sections' => array(
                    array('heading' => $text('mod_essay_help_submit_online_heading'), 'body' => $text('mod_essay_help_submit_online_body')),
                    array('heading' => $text('mod_essay_help_submit_upload_heading'), 'body' => $text('mod_essay_help_submit_upload_body')),
                    array('heading' => $text('mod_essay_help_submit_draft_heading'), 'body' => $text('mod_essay_help_submit_draft_body')),
                    array('heading' => $text('mod_essay_help_submit_after_heading'), 'body' => $text('mod_essay_help_submit_after_body')),
                ),
            );
        }
        if ($topicId !== 'ai-assisted-marking') { return null; }
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
