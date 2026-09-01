<?php
/** Default essay-level rubric definition and provisioning. */
if (empty($GLOBALS['kewl_entry_point_run'])) { die('You cannot view this page directly'); }
/** Supplies the protected default rubric used by long-form Essays. @package essay @author Derek Keats */
class essaydefaultrubric extends ChisimbaObject
{
    const RUBRIC_ID = 'essay-default-rubric-v1';
    /** @return array|false Structured canonical rubric. */
    public function getStructuredRubric()
    {
        $service = $this->getObject('rubricservice', 'rubric');
        return $service->ensureRubricTemplate(self::definition()) ? $service->getStructuredRubric(self::RUBRIC_ID) : false;
    }
    /** @return array Versioned default rubric data without presentation markup. */
    public static function definition()
    {
        return array(
            'id'=>self::RUBRIC_ID, 'contextCode'=>'root', 'title'=>'Default essay rubric',
            'description'=>'General long-form rubric for a focused, evidence-based and coherent response.',
            'performances'=>array('Insufficient','Developing','Competent','Excellent'),
            'criteria'=>array(
                array('objective'=>'Response to the task and argument','levels'=>array('Does not address the task or present a discernible argument.','Addresses part of the task; the position is limited, unclear or inconsistent.','Addresses the task directly and sustains a clear argument.','Develops a compelling, precise argument that fully addresses the task.')),
                array('objective'=>'Knowledge and accuracy','levels'=>array('Shows little relevant knowledge or contains serious factual errors.','Shows partial knowledge, with important omissions or inaccuracies.','Uses relevant and mostly accurate knowledge, with only minor gaps.','Demonstrates comprehensive, accurate and well-selected knowledge.')),
                array('objective'=>'Analysis and reasoning','levels'=>array('Relies on assertion, description or unsupported opinion.','Offers some explanation, but analysis is uneven or relationships are weakly developed.','Explains important relationships and supports conclusions with sound reasoning.','Provides perceptive, sustained analysis and evaluates complexity convincingly.')),
                array('objective'=>'Evidence and support','levels'=>array('Provides little relevant evidence or uses evidence inaccurately.','Provides some relevant support, but its selection or explanation is limited.','Uses appropriate evidence and explains how it supports the argument.','Integrates well-chosen evidence critically and persuasively throughout.')),
                array('objective'=>'Organisation and coherence','levels'=>array('Is fragmented or difficult to follow, with no effective structure.','Has a recognisable structure, but progression or paragraphing is uneven.','Is logically organised and coherent, with effective paragraph development.','Is exceptionally well structured, fluent and purposeful from introduction to conclusion.')),
                array('objective'=>'Academic expression','levels'=>array('Language frequently obscures meaning and presentation does not meet basic expectations.','Meaning is generally understandable, though expression and conventions are inconsistent.','Uses clear, appropriate language and follows expected academic conventions.','Uses precise, confident language and academic conventions with excellent control.')),
            ),
        );
    }
}
?>
