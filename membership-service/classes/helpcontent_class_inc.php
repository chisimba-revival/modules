<?php
/** Contextual Help topics owned by Membership Service. @package membership-service */
class helpcontent extends ChisimbaObject
{
    public function init()
    {
        $this->language = $this->getObject('language', 'language');
        $this->authorization = $this->getObject('membershipauthorizationservice', 'membership-service');
    }

    public function mayViewTopic($topicId)
    {
        return $topicId === 'membership-operations'
            && $this->authorization->can('membership.view');
    }

    public function getTopic($topicId)
    {
        if ($topicId !== 'membership-operations') { return null; }
        $text = function ($code) {
            return $this->language->code2Txt($code, 'membership-service');
        };
        return array(
            'title' => $text('mod_membership_service_help_operations_title'),
            'summary' => $text('mod_membership_service_help_operations_summary'),
            'steps' => array(
                $text('mod_membership_service_help_operations_step_person'),
                $text('mod_membership_service_help_operations_step_period'),
                $text('mod_membership_service_help_operations_step_reason'),
                $text('mod_membership_service_help_operations_step_check'),
            ),
            'sections' => array(
                array('heading' => $text('mod_membership_service_help_manual_heading'), 'body' => $text('mod_membership_service_help_manual_body')),
                array('heading' => $text('mod_membership_service_help_lifecycle_heading'), 'body' => $text('mod_membership_service_help_lifecycle_body')),
                array('heading' => $text('mod_membership_service_help_roles_heading'), 'body' => $text('mod_membership_service_help_roles_body')),
                array('heading' => $text('mod_membership_service_help_history_heading'), 'body' => $text('mod_membership_service_help_history_body')),
            ),
        );
    }
}
?>
