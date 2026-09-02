<?php
/** Default essay-level rubric definition and provisioning. */
if (empty($GLOBALS['kewl_entry_point_run'])) { die('You cannot view this page directly'); }
/** Supplies the protected default rubric used by long-form Essays. @package essay @author Derek Keats */
class essaydefaultrubric extends ChisimbaObject
{
    const RUBRIC_ID = 'essay-default-rubric-v2';
    /** @return array|false Structured canonical rubric. */
    public function getStructuredRubric()
    {
        $service = $this->getObject('rubricservice', 'rubric');
        if (!$service->ensureRubricTemplate(self::definition())) { return false; }
        $rubric = $service->getStructuredRubric(self::RUBRIC_ID);
        if (!is_array($rubric)) { return false; }
        $definition = self::definition();
        foreach ($rubric['criteria'] as $index => $criterion) {
            $rubric['criteria'][$index]['maximumMark'] = (int) $definition['criteria'][$index]['maximumMark'];
        }
        return $rubric;
    }
    /** @return array Versioned default rubric data without presentation markup. */
    public static function definition()
    {
        return array(
            'id'=>self::RUBRIC_ID, 'contextCode'=>'root', 'title'=>'Default essay rubric',
            'description'=>'Weighted long-form rubric for a sustained, accurate, analytical and well-supported response.',
            'performances'=>array('Insufficient','Developing','Competent','Excellent'),
            'criteria'=>array(
                array('objective'=>'Response to the task and argument','maximumMark'=>20,'levels'=>array('Does not address the task or present a discernible argument.','Addresses part of the task; the position is limited, unclear or inconsistent.','Addresses the whole task directly and sustains a clear argument.','Develops a compelling, precise and nuanced argument that fully addresses the task.')),
                array('objective'=>'Knowledge and accuracy','maximumMark'=>20,'levels'=>array('Shows little relevant knowledge or contains serious factual errors.','Shows partial knowledge, with important omissions or inaccuracies.','Uses relevant and mostly accurate knowledge, with only minor gaps.','Demonstrates comprehensive, accurate and judiciously selected knowledge.')),
                array('objective'=>'Analysis, synthesis and judgement','maximumMark'=>20,'levels'=>array('Relies on assertion, description or unsupported opinion.','Offers some explanation, but analysis is uneven and ideas are weakly connected.','Explains important relationships, connects ideas and supports conclusions with sound reasoning.','Provides perceptive, sustained analysis, synthesises ideas and evaluates complexity convincingly.')),
                array('objective'=>'Evidence and support','maximumMark'=>15,'levels'=>array('Provides little relevant support or uses evidence inaccurately.','Provides some relevant support, but its selection, verification or explanation is limited.','Uses appropriate examples or evidence and explains how they support the argument.','Integrates well-chosen, credible support critically and persuasively throughout.')),
                array('objective'=>'Organisation and coherence','maximumMark'=>10,'levels'=>array('Is fragmented or difficult to follow, with no effective structure.','Has a recognisable structure, but progression or paragraphing is uneven.','Is logically organised and coherent, with effective paragraph development.','Is exceptionally well structured, fluent and purposeful from introduction to conclusion.')),
                array('objective'=>'Academic expression','maximumMark'=>10,'levels'=>array('Language frequently obscures meaning and presentation does not meet basic expectations.','Meaning is generally understandable, though expression and conventions are inconsistent.','Uses clear, appropriate language and follows expected academic conventions.','Uses precise, confident language and academic conventions with excellent control.')),
                array('objective'=>'Depth and independent engagement','maximumMark'=>5,'levels'=>array('Shows no meaningful engagement beyond superficial or generic statements.','Shows limited depth or independent engagement with the particular task.','Develops relevant detail and demonstrates purposeful engagement with the task.','Shows distinctive insight, intellectual independence and well-proportioned depth.')),
            ),
        );
    }
}
?>
