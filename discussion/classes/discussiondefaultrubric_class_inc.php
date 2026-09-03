<?php
/** Built-in participation and quality rubric for marked Discussions. @author Derek Keats */
if (empty($GLOBALS['kewl_entry_point_run'])) { die('You cannot view this page directly'); }
class discussiondefaultrubric extends ChisimbaObject
{
    const RUBRIC_ID = 'discussion-participation-quality-v1';
    public function getStructuredRubric()
    {
        $service=$this->getObject('rubricservice','rubric');
        if(!$service->ensureRubricTemplate(self::definition())){return false;}
        $rubric=$service->getStructuredRubric(self::RUBRIC_ID);$definition=self::definition();
        if(!is_array($rubric)){return false;}
        foreach($rubric['criteria'] as $index=>$criterion){$rubric['criteria'][$index]['maximumMark']=$definition['criteria'][$index]['maximumMark'];}
        return $rubric;
    }
    public static function definition()
    {
        return array(
            'id'=>self::RUBRIC_ID,'contextCode'=>'root','title'=>'Discussion participation and quality',
            'description'=>'Default evidence-based rubric for the consistency, relevance, reasoning and constructive quality of course discussion contributions.',
            'performances'=>array('Insufficient','Developing','Competent','Excellent'),
            'criteria'=>array(
                array('objective'=>'Participation, consistency and development','maximumMark'=>25,'levels'=>array('Makes no meaningful contribution.','Contributes rarely or in a concentrated burst with limited continuity.','Contributes regularly across the discussion period, with sound sustained quality or visible improvement.','Sustains timely, purposeful participation at a high standard, or develops meaningfully toward that standard, while helping the conversation progress.')),
                array('objective'=>'Relevance to the discussion','maximumMark'=>25,'levels'=>array('Contributions are absent or unrelated.','Some contributions relate to the topic but are often generic or tangential.','Contributions address the topic and respond appropriately to the discussion.','Contributions are consistently focused, perceptive and move the particular discussion forward.')),
                array('objective'=>'Quality of reasoning and evidence','maximumMark'=>30,'levels'=>array('Makes unsupported assertions or contains serious inaccuracies.','Offers limited explanation or evidence, with uneven reasoning.','Explains positions clearly and uses appropriate examples or evidence.','Develops accurate, well-supported and critically reasoned contributions with insight.')),
                array('objective'=>'Constructive engagement and responsiveness','maximumMark'=>20,'levels'=>array('Does not engage constructively or undermines participation.','Acknowledges others occasionally but adds little to their ideas or feedback.','Responds respectfully, asks useful questions and builds on other contributions; may also apply feedback over time.','Synthesises perspectives, invites participation and constructively advances collective understanding, through consistently strong engagement or meaningful responsiveness and development.')),
            ),
        );
    }
}
