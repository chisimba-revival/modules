<?php
/** Default worksheet-level rubric definition and provisioning. */
if (empty($GLOBALS['kewl_entry_point_run'])) { die('You cannot view this page directly'); }

/**
 * Supplies the protected default rubric used by Online Worksheets.
 *
 * @package worksheet
 * @author Derek Keats
 */
class worksheetdefaultrubric extends ChisimbaObject
{
    const RUBRIC_ID = 'worksheet-default-rubric-v1';

    /**
     * Ensure the canonical rubric data exists and return its structured form.
     *
     * @return array|false
     */
    public function getStructuredRubric()
    {
        $service = $this->getObject('rubricservice', 'rubric');
        if (!$service->ensureRubricTemplate(self::definition())) {
            return false;
        }
        return $service->getStructuredRubric(self::RUBRIC_ID);
    }

    /**
     * Return the versioned default data without presentation markup.
     *
     * @return array
     */
    public static function definition()
    {
        return array(
            'id' => self::RUBRIC_ID,
            'contextCode' => 'root',
            'title' => 'Default worksheet rubric',
            'description' => 'General written-answer rubric. High marks require accuracy and completeness. Suggested bands: 90-100 comprehensive; 75-89 strong with limited omissions; 50-74 partial with substantial omissions; 25-49 limited; 0-24 insufficient.',
            'performances' => array('Insufficient', 'Developing', 'Competent', 'Excellent'),
            'criteria' => array(
                array(
                    'objective' => 'Accuracy',
                    'levels' => array(
                        'The answer is absent, largely incorrect, or shows serious misconceptions.',
                        'Some ideas are correct, but important errors or misunderstandings remain.',
                        'The answer is mostly accurate, with only minor errors.',
                        'The answer is accurate throughout and demonstrates secure knowledge.',
                    ),
                ),
                array(
                    'objective' => 'Coverage and completeness',
                    'levels' => array(
                        'The answer omits most of the knowledge required by the question.',
                        'The answer includes some relevant knowledge but misses important concepts or details.',
                        'The answer covers the main requirements, with limited omissions.',
                        'The answer is comprehensive and addresses all, or almost all, essential concepts.',
                    ),
                ),
                array(
                    'objective' => 'Understanding and reasoning',
                    'levels' => array(
                        'The answer gives little evidence of understanding or meaningful explanation.',
                        'The answer shows partial understanding but relies on unsupported statements or simple recall.',
                        'The answer explains the main ideas and relevant relationships clearly.',
                        'The answer demonstrates strong understanding, explains important relationships, and uses sound reasoning.',
                    ),
                ),
                array(
                    'objective' => 'Relevance and clarity',
                    'levels' => array(
                        'The response is unclear, largely irrelevant, or does not answer the question.',
                        'The response is partly relevant but may be unclear, poorly focused, or difficult to follow.',
                        'The response answers the question directly and communicates its ideas clearly.',
                        'The response is focused, well organised, concise and exceptionally clear.',
                    ),
                ),
            ),
        );
    }
}
?>
