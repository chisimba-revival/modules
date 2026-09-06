<?php
/** Contextual Help topics owned by Payment Service. @package payment-service */
class helpcontent extends ChisimbaObject
{
    public function init()
    {
        $this->language = $this->getObject('language', 'language');
        $this->authorization = $this->getObject('membershipauthorizationservice', 'membership-service');
    }

    public function mayViewTopic($topicId)
    {
        return $topicId === 'payment-operations'
            && $this->authorization->can('payment.view');
    }

    public function getTopic($topicId)
    {
        if ($topicId !== 'payment-operations') { return null; }
        $text = function ($code) {
            return $this->language->code2Txt($code, 'payment-service');
        };
        return array(
            'title' => $text('mod_payment_service_help_operations_title'),
            'summary' => $text('mod_payment_service_help_operations_summary'),
            'steps' => array(
                $text('mod_payment_service_help_operations_step_intent'),
                $text('mod_payment_service_help_operations_step_event'),
                $text('mod_payment_service_help_operations_step_access'),
                $text('mod_payment_service_help_operations_step_escalate'),
            ),
            'sections' => array(
                array('heading' => $text('mod_payment_service_help_states_heading'), 'body' => $text('mod_payment_service_help_states_body')),
                array('heading' => $text('mod_payment_service_help_confirmation_heading'), 'body' => $text('mod_payment_service_help_confirmation_body')),
                array('heading' => $text('mod_payment_service_help_duplicates_heading'), 'body' => $text('mod_payment_service_help_duplicates_body')),
                array('heading' => $text('mod_payment_service_help_support_heading'), 'body' => $text('mod_payment_service_help_support_body')),
            ),
        );
    }
}
?>
