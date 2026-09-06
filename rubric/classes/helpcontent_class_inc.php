<?php
/** Contextual Help topics owned by Rubrics. @package rubric */
class helpcontent extends ChisimbaObject
{
    public function init()
    {
        $this->language = $this->getObject('language', 'language');
        $this->user = $this->getObject('user', 'security');
    }

    public function mayViewTopic($topicId)
    {
        return $topicId === 'creating-and-editing-rubrics'
            && $this->user->isLoggedIn()
            && ($this->user->isAdmin() || $this->user->isLecturer());
    }

    public function getTopic($topicId)
    {
        if ($topicId !== 'creating-and-editing-rubrics') { return null; }
        $text = function ($code) {
            return html_entity_decode(
                $this->language->code2Txt($code, 'rubric'),
                ENT_QUOTES | ENT_HTML5,
                'UTF-8'
            );
        };
        return array(
            'title' => $text('mod_rubric_help_build_title'),
            'summary' => $text('mod_rubric_help_build_summary'),
            'steps' => array(
                $text('mod_rubric_help_build_step_default'),
                $text('mod_rubric_help_build_step_criteria'),
                $text('mod_rubric_help_build_step_levels'),
                $text('mod_rubric_help_build_step_review'),
            ),
            'sections' => array(
                array('heading' => $text('mod_rubric_help_defaults_heading'), 'body' => $text('mod_rubric_help_defaults_body')),
                array('heading' => $text('mod_rubric_help_matrix_heading'), 'body' => $text('mod_rubric_help_matrix_body')),
                array('heading' => $text('mod_rubric_help_scope_heading'), 'body' => $text('mod_rubric_help_scope_body')),
                array('heading' => $text('mod_rubric_help_judgement_heading'), 'body' => $text('mod_rubric_help_judgement_body')),
                array('heading' => $text('mod_rubric_help_practical_heading'), 'body' => $text('mod_rubric_help_practical_body')),
            ),
        );
    }
}
?>
