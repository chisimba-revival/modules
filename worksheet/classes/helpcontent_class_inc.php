<?php
/** Contextual Help topics owned by Online Worksheets. @package worksheet */
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
        $contextCode = $this->context->getContextCode();
        return $contextCode !== ''
            && $this->user->isContextLecturer($this->user->userId(), $contextCode);
    }

    public function getTopic($topicId)
    {
        if ($topicId !== 'ai-assisted-marking') { return null; }
        $text = function ($code) {
            return $this->language->code2Txt($code, 'worksheet');
        };
        return array(
            'title' => $text('mod_worksheet_help_ai_title'),
            'summary' => $text('mod_worksheet_help_ai_summary'),
            'steps' => array(
                $text('mod_worksheet_help_ai_step_review'),
                $text('mod_worksheet_help_ai_step_suggest'),
                $text('mod_worksheet_help_ai_step_wait'),
                $text('mod_worksheet_help_ai_step_check'),
                $text('mod_worksheet_help_ai_step_adjust'),
                $text('mod_worksheet_help_ai_step_save'),
            ),
            'sections' => array(
                array('heading' => $text('mod_worksheet_help_ai_control_heading'), 'body' => $text('mod_worksheet_help_ai_control_body')),
                array('heading' => $text('mod_worksheet_help_ai_evidence_heading'), 'body' => $text('mod_worksheet_help_ai_evidence_body')),
                array('heading' => $text('mod_worksheet_help_ai_failure_heading'), 'body' => $text('mod_worksheet_help_ai_failure_body')),
                array('heading' => $text('mod_worksheet_help_ai_gradebook_heading'), 'body' => $text('mod_worksheet_help_ai_gradebook_body')),
            ),
        );
    }
}
?>
