<?php
/** Contextual Help topics owned by Gradebook. @package gradebook */
class helpcontent extends ChisimbaObject
{
    public function init()
    {
        $this->language = $this->getObject('language', 'language');
        $this->user = $this->getObject('user', 'security');
        $this->context = $this->getObject('dbcontext', 'context');
        $this->permission = $this->getObject('contextcondition', 'contextpermissions');
    }

    public function mayViewTopic($topicId)
    {
        if ($topicId !== 'assessment-plan-and-sheet' || !$this->user->isLoggedIn()) {
            return false;
        }
        return $this->context->getContextCode() !== ''
            && ($this->user->isAdmin() || $this->permission->isContextMember('Lecturers'));
    }

    public function getTopic($topicId)
    {
        if ($topicId !== 'assessment-plan-and-sheet') { return null; }
        $text = function ($code) {
            return html_entity_decode(
                $this->language->code2Txt($code, 'gradebook'),
                ENT_QUOTES | ENT_HTML5,
                'UTF-8'
            );
        };
        return array(
            'title' => $text('mod_gradebook_help_plan_title'),
            'summary' => $text('mod_gradebook_help_plan_summary'),
            'steps' => array(
                $text('mod_gradebook_help_plan_step_create'),
                $text('mod_gradebook_help_plan_step_include'),
                $text('mod_gradebook_help_plan_step_weight'),
                $text('mod_gradebook_help_plan_step_marks'),
                $text('mod_gradebook_help_plan_step_check'),
            ),
            'sections' => array(
                array('heading' => $text('mod_gradebook_help_plan_difference_heading'), 'body' => $text('mod_gradebook_help_plan_difference_body')),
                array('heading' => $text('mod_gradebook_help_plan_marks_heading'), 'body' => $text('mod_gradebook_help_plan_marks_body')),
                array('heading' => $text('mod_gradebook_help_plan_weight_heading'), 'body' => $text('mod_gradebook_help_plan_weight_body')),
                array('heading' => $text('mod_gradebook_help_plan_change_heading'), 'body' => $text('mod_gradebook_help_plan_change_body')),
            ),
        );
    }
}
?>
