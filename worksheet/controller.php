<?php
/**
 *
* Controller class for the worksheet module.
*
* Worksheet provides functionality for lectures to create, edit and delete worksheets and mark
* answered worksheets submitted by the students in the context.
*
* Functionality is provided for students to answer the worksheet and submit it for marking, and
* view the marked worksheet.
 *
 * PHP version 5
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the
 * Free Software Foundation, Inc.,
 * 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 *
 * @category  Chisimba
 * @package   worksheet
 * @author    Tohir Solomons tsolomons@uwc.ac.za
 * @copyright 2007 AVOIR
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt The GNU General Public License
 * @version   $Id$
 * @link      http://avoir.uwc.ac.za
 */

// security check - must be included in all scripts
if (!
/**
 * The $GLOBALS is an array used to control access to certain constants.
 * Here it is used to check if the file is opening in engine, if not it
 * stops the file from running.
 *
 * @global entry point $GLOBALS['kewl_entry_point_run']
 * @name   $kewl_entry_point_run
 *
 */
$GLOBALS['kewl_entry_point_run'])
{
        die("You cannot view this page directly");
}
// end security check

/**
*
* Controller class for the worksheet module.
*
* Worksheet provides functionality for lectures to create, edit and delete worksheets and mark
* answered worksheets submitted by the students in the context.
*
* Functionality is provided for students to answer the worksheet and submit it for marking, and
* view the marked worksheet.
*
* @author Tohir Solomons
* @package worksheet
*
*/
class worksheet extends controller
{

    public $objConfig;
    public $objLanguage;
    public $objLog;
    public $rubricAvailable = FALSE;
    public $objRubricService = NULL;
    public $objAiMarker = NULL;
    public $aiMarkingAvailable = FALSE;
    public $objAiMarkingJobs = NULL;

    public function init()
    {
        $this->objUser = $this->getObject('user', 'security');
        $this->objLanguage = $this->getObject('language', 'language');
        $this->objConfig = $this->getObject('config', 'config');
        $this->objLog=$this->newObject('logactivity', 'logger');
        $this->objLog->log();

        $this->objWashout = $this->getObject('washout','utilities');
        $this->objContext = $this->getObject('dbcontext', 'context');
        $this->contextCode = $this->objContext->getContextCode();

        $this->objWorksheet = $this->getObject('dbworksheet', 'worksheet');
        $this->objWorksheetQuestions = $this->getObject('dbworksheetquestions', 'worksheet');
        $this->objWorksheetAnswers = $this->getObject('dbworksheetanswers', 'worksheet');
        $this->objWorksheetResults = $this->getObject('dbworksheetresults', 'worksheet');
        $this->objModuleCatalogue = $this->getObject('modules', 'modulecatalogue');
        $this->objContextGroups = $this->getObject('managegroups', 'contextgroups');

        if ($this->objModuleCatalogue->checkIfRegistered('rubric')) {
            $this->objRubricService = $this->getObject('rubricservice', 'rubric');
            $this->rubricAvailable = TRUE;
        }

        $this->objAiMarker = $this->getObject('worksheetaimarker', 'worksheet');
        $this->aiMarkingAvailable = $this->objAiMarker->isAvailable();
        $this->objAiMarkingJobs = $this->getObject('dbworksheetaimarkingjobs', 'worksheet');

        if($this->objModuleCatalogue->checkIfRegistered('activitystreamer'))
        {
            $this->objActivityStreamer = $this->getObject('activityops', 'activitystreamer');
            $this->eventDispatcher->addObserver ( array ($this->objActivityStreamer, 'postmade' ) );
            $this->eventsEnabled = TRUE;
        } else {
            $this->eventsEnabled = FALSE;
        }
    }

    public function isValid($action, $default = true)
    {
        $lecturerActions = array(
            'add', 'deleteworksheet', 'saveworksheet', 'saveworksheetedit', 'worksheetinfo',
            'managequestions', 'savequestion', 'activate', 'updatestatus', 'viewstudentworksheet',
            'savestudentmark', 'aiassistmark', 'aimarkingjob', 'editquestion', 'updatequestion', 'deletequestion', 'preview'
        );

        if (in_array($action, $lecturerActions)) {
            if ($this->objUser->isContextLecturer($this->objUser->userid(),$this->contextCode)) {
                return TRUE;
            } else {
                return FALSE;
            }
        } else {
            return TRUE;
        }
    }

    public function dispatch($action='home')
    {
        if ($this->contextCode == '') {
            return $this->nextAction(NULL, array('error'=>'notincontext'), '_default');
        }

        if (!$this->isValid($action)) {
            return $this->nextAction(NULL);
        }

        $this->setLayoutTemplate('context_layout_tpl.php');
        $method = $this->__getMethod($action);
        return $this->$method();
    }

    function __validAction(& $action)
    {
        if (method_exists($this, "__".$action)) {
            return TRUE;
        } else {
            return FALSE;
        }
    }

    function __getMethod(& $action)
    {
        if ($this->__validAction($action)) {
            return "__" . $action;
        } else {
            return "__home";
        }
    }

    /**
     * Return reusable root rubrics plus rubrics owned by the current context.
     * Rubric remains an optional module; an empty array means no selector is shown.
     */
    private function getAvailableRubrics()
    {
        if (!$this->rubricAvailable || !is_object($this->objRubricService)) {
            return array();
        }

        $rubrics = array();
        $seen = array();
        $contexts = array('root');
        if ($this->contextCode !== 'root') {
            $contexts[] = $this->contextCode;
        }

        foreach ($contexts as $contextCode) {
            foreach ($this->objRubricService->listRubrics($contextCode) as $rubric) {
                if (!empty($rubric['id']) && !isset($seen[$rubric['id']])) {
                    $rubrics[] = $rubric;
                    $seen[$rubric['id']] = TRUE;
                }
            }
        }

        return $rubrics;
    }

    /**
     * Accept a rubric reference only when it is available to this context.
     */
    private function getValidRubricId($rubricId)
    {
        if (!is_string($rubricId) || $rubricId === '') {
            return '';
        }

        foreach ($this->getAvailableRubrics() as $rubric) {
            if ($rubric['id'] === $rubricId) {
                return $rubricId;
            }
        }

        return '';
    }

    private function __home()
    {
        $worksheets = $this->objWorksheet->getWorksheetsInContext($this->contextCode);
        $this->setVarByRef('worksheets', $worksheets);
        return 'home_tpl.php';
    }

    private function __add()
    {
        $this->setVar('mode', 'add');
        return 'step1_tpl.php';
    }

    private function __edit()
    {
        $this->setVar('mode', 'edit');
        $this->setVar('worksheet', $this->objWorksheet->getWorksheet($this->getParam("id")));
        return 'step1_tpl.php';
    }

    private function __saveworksheetedit()
    {
        $title = $this->getParam('title');
        $id = $this->getParam('id');
        $description = $this->getParam('description');
        $date = $this->getParam('calendardate');
        $time = $this->getParam('time');
        $activity_status = $this->getParam('activity_status');
        $classification = $this->getParam('classification', 'unclassified');
        if (!in_array($classification, array('formative', 'summative'), true)) { $classification = 'unclassified'; }
        $closing_date = $date.' '.$time;
        $lastUpdated = strftime('%Y-%m-%d %H:%M:%S', time());

        $id = $this->objWorksheet->updateWorkSheet($id, $this->contextCode, $title, $activity_status, $classification, $closing_date, $description, $this->objUser->userId(), $lastUpdated);
        return $this->nextAction('home');
    }

    private function __saveworksheet()
    {
        $title = $this->getParam('title');
        $description = $this->getParam('description');
        $date = $this->getParam('calendardate');
        $time = $this->getParam('time');
        $activity_status = 'inactive';
        $classification = $this->getParam('classification', 'unclassified');
        if (!in_array($classification, array('formative', 'summative'), true)) { $classification = 'unclassified'; }
        $closing_date = $date.' '.$time;
        if ($this->eventsEnabled) {
            $message = $this->objUser->getSurname()." ".$this->objLanguage->languageText('mod_worksheet_newalert', 'worksheet')." ".$this->contextCode;
            $this->eventDispatcher->post($this->objActivityStreamer, "context", array(
                'title' => $message,
                'link' => $this->uri(array()),
                'contextcode' => $this->objContext->getContextCode(),
                'author' => $this->objUser->fullname(),
                'description' => $message
            ));
        }

        $id = $this->objWorksheet->insertWorkSheet($this->contextCode, NULL, $title, $activity_status, $classification, $closing_date, $description );
        return $this->nextAction('managequestions', array('id'=>$id));
    }

    private function __deleteworksheet()
    {
        $id = $this->getParam('id');
        $this->objWorksheet->deleteWorksheet($id);
        return $this->nextAction('home', array());
    }

    private function __worksheetinfo()
    {
        $this->setVar('mode', 'edit');
        $id = $this->getParam('id');
        $worksheet = $this->objWorksheet->getWorksheet($id);
        if ($worksheet == FALSE) {
            return $this->nextAction(NULL, array('error'=>'unknownworksheet'));
        }

        $this->setVarByRef('id', $id);
        $this->setVarByRef('worksheet', $worksheet);
        $questions = $this->objWorksheetQuestions->getQuestions($id);
        $this->setVarByRef('questions', $questions);
        $worksheetResults = $this->objWorksheetResults->getResults($id);
        $this->setVarByRef('worksheetResults', $worksheetResults);
        return 'worksheetinfo_tpl.php';
    }

    private function __managequestions()
    {
        $id = $this->getParam('id');
        $worksheet = $this->objWorksheet->getWorksheet($id);
        if ($worksheet == FALSE) {
            return $this->nextAction(NULL);
        }
        if ($worksheet['context'] != $this->contextCode) {
            return $this->nextAction(NULL);
        }

        $this->setVarByRef('id', $id);
        $this->setVarByRef('worksheet', $worksheet);
        $questions = $this->objWorksheetQuestions->getQuestions($id);
        $this->setVarByRef('questions', $questions);
        $rubrics = $this->getAvailableRubrics();
        $this->setVarByRef('rubrics', $rubrics);
        $this->setVar('rubricAvailable', $this->rubricAvailable);
        return 'step2_tpl.php';
    }

    private function __savequestion()
    {
        $question = $this->getParam('question');
        $modelanswer = $this->getParam('modelanswer');
        $question_worth = $this->getParam('mark');
        $worksheet_id = $this->getParam('worksheet');
        $rubricId = $this->getValidRubricId($this->getParam('rubric_id'));

        $result = $this->objWorksheetQuestions->insertSingle($worksheet_id, $question, $modelanswer, $question_worth);
        if ($result && $rubricId !== '') {
            $this->objWorksheetQuestions->updateQn('id', $result, array('rubric_id' => $rubricId));
        }

        return $this->nextAction('managequestions', array('msg'=>'questionadded', 'id'=>$worksheet_id, 'question'=>$result));
    }

    private function __activate()
    {
        $this->setVar('mode', 'edit');
        $id = $this->getParam('id');
        $worksheet = $this->objWorksheet->getWorksheet($id);
        if ($worksheet == FALSE) {
            return $this->nextAction(NULL, array('error'=>'unknownworksheet'));
        }
        $this->setVarByRef('id', $id);
        $this->setVarByRef('worksheet', $worksheet);
        $questions = $this->objWorksheetQuestions->getQuestions($id);
        $this->setVarByRef('questions', $questions);
        return 'step3_tpl.php';
    }

    private function __updatestatus()
    {
        $id = $this->getParam('id');
        $activityStatus = $this->getParam('activity_status');
        $closingDate = $this->getParam('calendardate').' '.$this->getParam('time');
        $result = $this->objWorksheet->updateStatus($id, $activityStatus, $closingDate);
        if ($result) {
            return $this->nextAction(NULL, array('message'=>'statusupdate', 'id'=>$id));
        } else {
            return $this->nextAction(NULL, array('error'=>'unabletofindworksheet'));
        }
    }

    private function __viewworksheet()
    {
        $id = $this->getParam('id');
        $worksheet = $this->objWorksheet->getWorksheet($id);
        if ($worksheet == FALSE) {
            return $this->nextAction(NULL, array('error'=>'unknownworksheet'));
        }
        $this->setVarByRef('id', $id);
        $this->setVarByRef('worksheet', $worksheet);
        $questions = $this->objWorksheetQuestions->getQuestions($id);
        $this->setVarByRef('questions', $questions);
        $worksheetResult = $this->objWorksheetResults->getWorksheetResult($this->objUser->userId(), $id);
        if ($worksheet['activity_status'] == 'open' && !$worksheetResult) {
            return 'answerworksheet_tpl.php';
        } else {
            $this->setVarByRef('worksheetResult', $worksheetResult);
            return 'viewworksheet_tpl.php';
        }
    }

    private function __preview()
    {
        $id = $this->getParam('id');
        $worksheet = $this->objWorksheet->getWorksheet($id);
        if ($worksheet == FALSE) {
            return $this->nextAction(NULL, array('error'=>'unknownworksheet'));
        }
        $this->setVarByRef('id', $id);
        $this->setVarByRef('worksheet', $worksheet);
        $questions = $this->objWorksheetQuestions->getQuestions($id);
        $this->setVarByRef('questions', $questions);
        $this->setLayoutTemplate(NULL);
        $this->setVar('pageSuppressToolbar', TRUE);
        $this->setVar('pageSuppressBanner', TRUE);
        $this->setVar('pageSuppressSearch', TRUE);
        $this->setVar('suppressFooter', TRUE);
        return 'preview_tpl.php';
    }

    private function __saveanswers()
    {
        $id = $this->getParam('id');
        $worksheet = $this->objWorksheet->getWorksheet($id);
        if ($worksheet == FALSE) {
            return $this->nextAction(NULL, array('error'=>'unknownworksheet'));
        }
        if ($this->getParam('user') != $this->objUser->userId()) {
            return $this->nextAction(NULL, array('error'=>'userswitched'));
        }
        $answersSaved = $this->objWorksheetAnswers->saveAnswers($id, $this->objUser->userId());
        if (!$answersSaved) {
            return $this->nextAction('viewworksheet', array('error'=>'answersnotsaved', 'id'=>$id));
        }
        if (isset($_POST['saveandclose'])) {
            $this->objWorksheetResults->setWorksheetCompleted($this->objUser->userId(), $id);
            return $this->nextAction(NULL, array('message'=>'worksheetsaved', 'id'=>$id));
        } else {
            return $this->nextAction('viewworksheet', array('message'=>'worksheetsaved', 'id'=>$id));
        }
    }

    private function __viewstudentworksheet()
    {
        $resultId = $this->getParam('id');
        $result = $this->objWorksheetResults->getRow('id', $resultId);
        if ($result == FALSE) {
            return $this->nextAction(NULL, array('error'=>'resultnotavailable'));
        }
        $worksheet = $this->objWorksheet->getWorksheet($result['worksheet_id']);
        if ($worksheet == FALSE) {
            return $this->nextAction(NULL, array('error'=>'unknownworksheet'));
        }

        $this->setVarByRef('id', $result['worksheet_id']);
        $this->setVarByRef('worksheet', $worksheet);
        $questions = $this->objWorksheetQuestions->getQuestions($result['worksheet_id']);
        $this->setVarByRef('questions', $questions);
        $this->setVarByRef('worksheetResult', $result);

        $structuredRubrics = array();
        if ($this->rubricAvailable) {
            foreach ($questions as $question) {
                if (!empty($question['rubric_id'])) {
                    $rubric = $this->objRubricService->getStructuredRubric($question['rubric_id']);
                    if ($rubric !== FALSE) {
                        $structuredRubrics[$question['id']] = $rubric;
                    }
                }
            }
        }
        $this->setVarByRef('structuredRubrics', $structuredRubrics);
        $this->setVar('aiMarkingAvailable', $this->aiMarkingAvailable);
        $this->setVar('aiSuggestions', array());
        $stack = $this->getObject('nativeauthwebcomposition', 'security')->build();
        $this->setVar('worksheetMarkToken', $stack['csrf']->issue('worksheet_save_marks'));
        if ($this->aiMarkingAvailable) {
            $this->setVar('aiMarkingToken', $stack['csrf']->issue('worksheet_ai_marking'));
        }

        return 'viewstudentworksheet_tpl.php';
    }

    private function __aiassistmark()
    {
        if (!$this->aiMarkingAvailable) { return $this->nextAction(NULL); }
        $stack = $this->getObject('nativeauthwebcomposition', 'security')->build();
        if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? '')) !== 'POST'
            || !$stack['csrf']->consume('worksheet_ai_marking', (string) $this->getParam('csrf_token', ''))) {
            return $this->nextAction(NULL);
        }
        $resultId = $this->getParam('id');
        $result = $this->objWorksheetResults->getRow('id', $resultId);
        if ($result == FALSE) { return $this->nextAction(NULL, array('error'=>'resultnotavailable')); }
        $worksheet = $this->objWorksheet->getWorksheet($result['worksheet_id']);
        if ($worksheet == FALSE || $worksheet['context'] !== $this->contextCode) {
            return $this->nextAction(NULL, array('error'=>'unknownworksheet'));
        }
        $jobId = $this->objAiMarkingJobs->enqueue($this->contextCode, $this->objUser->userId(), $resultId);
        return empty($jobId) ? $this->nextAction('viewstudentworksheet', array('id'=>$resultId)) : $this->nextAction('aimarkingjob', array('id'=>$jobId));
    }

    private function __aimarkingjob()
    {
        $job = $this->objAiMarkingJobs->getOwned($this->getParam('id', ''), $this->contextCode, $this->objUser->userId(), $this->objUser->isAdmin());
        if (!is_array($job)) { return $this->nextAction(NULL); }
        if ($job['status'] === 'completed') {
            $result = $this->objWorksheetResults->getRow('id', $job['result_id']);
            if (!is_array($result)) { return $this->nextAction(NULL); }
            $worksheet = $this->objWorksheet->getWorksheet($result['worksheet_id']);
            if (!is_array($worksheet) || (string) $worksheet['context'] !== (string) $this->contextCode) {
                return $this->nextAction(NULL);
            }
            $questions = $this->objWorksheetQuestions->getQuestions($result['worksheet_id']);
            $rubrics = array();
            if ($this->rubricAvailable) {
                foreach ($questions as $question) {
                    if (!empty($question['rubric_id'])) {
                        $rubric = $this->objRubricService->getStructuredRubric($question['rubric_id']);
                        if ($rubric !== false) { $rubrics[$question['id']] = $rubric; }
                    }
                }
            }
            $this->setVarByRef('id', $result['worksheet_id']);
            $this->setVarByRef('worksheet', $worksheet);
            $this->setVarByRef('questions', $questions);
            $this->setVarByRef('worksheetResult', $result);
            $this->setVarByRef('structuredRubrics', $rubrics);
            $this->setVar('aiMarkingAvailable', $this->aiMarkingAvailable);
            $this->setVar('aiSuggestions', $job['suggestions']);
            $this->setVar('aiSuggestionError', '');
            $stack = $this->getObject('nativeauthwebcomposition', 'security')->build();
            $this->setVar('worksheetMarkToken', $stack['csrf']->issue('worksheet_save_marks'));
            if ($this->aiMarkingAvailable) {
                $this->setVar('aiMarkingToken', $stack['csrf']->issue('worksheet_ai_marking'));
            }
            return 'viewstudentworksheet_tpl.php';
        }
        $this->setVarByRef('aiMarkingJob', $job);
        return 'ai_marking_progress_tpl.php';
    }

    private function __savestudentmark()
    {
        $stack = $this->getObject('nativeauthwebcomposition', 'security')->build();
        if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? '')) !== 'POST'
            || !$stack['csrf']->consume('worksheet_save_marks', (string) $this->getParam('csrf_token', ''))) {
            return $this->nextAction(NULL);
        }
        $student = $this->getParam('student');
        $worksheet = $this->getParam('worksheet');
        $this->objWorksheetAnswers->saveMarks($student, $worksheet, $this->objUser->userId());
        $resultId = $this->objWorksheetResults->getWorksheetResult($student, $worksheet);
        if (is_array($resultId) && !empty($resultId['id'])) {
            $this->objAiMarkingJobs->deleteForResult($resultId['id']);
        }
        return $this->nextAction('viewstudentworksheet', array('id'=>$resultId['id'], 'message'=>'worksheetmarked'));
    }

    private function __editquestion()
    {
        $id = $this->getParam('id');
        $question = $this->objWorksheetQuestions->getQuestion($id);
        if ($question == FALSE) {
            return $this->nextAction(NULL);
        }
        $worksheet = $this->objWorksheet->getWorksheet($question['worksheet_id']);
        $numQuestions = $this->objWorksheetQuestions->getNumQuestions($question['worksheet_id']);
        $this->setVarByRef('question', $question);
        $this->setVarByRef('worksheet', $worksheet);
        $this->setVarByRef('id', $worksheet['id']);
        $this->setVarByRef('numQuestions', $numQuestions);
        $rubrics = $this->getAvailableRubrics();
        $this->setVarByRef('rubrics', $rubrics);
        $this->setVar('rubricAvailable', $this->rubricAvailable);
		$returnedRubricId = $this->getValidRubricId($this->getParam('rubric_id'));
		$this->setVar('returnedRubricId', $returnedRubricId);
        return 'editquestion_tpl.php';
    }

    private function __updatequestion()
    {
        $id = $this->getParam('id');
        $question = $this->getParam('question');
        $modelanswer = $this->getParam('modelanswer');
        $mark = $this->getParam('mark');
        $rubricId = $this->getValidRubricId($this->getParam('rubric_id'));

        $result = $this->objWorksheetQuestions->updateQuestion($id, $question, $modelanswer, $mark);
        if ($result) {
            $this->objWorksheetQuestions->updateQn('id', $id, array('rubric_id' => $rubricId === '' ? NULL : $rubricId));
            $questionInfo = $this->objWorksheetQuestions->getQuestion($id);
            return $this->nextAction('managequestions', array('id'=>$questionInfo['worksheet_id'], 'message'=>'questionupdated', 'question'=>$id));
        } else {
            return $this->nextAction(NULL, array('error'=>'couldnotupdatequestion'));
        }
    }

    private function __deletequestion()
    {
        $question = $this->getParam('question');
        $worksheet = $this->getParam('worksheet');
        if ($question == '' || $worksheet == '') {
            return $this->nextAction(NULL, array('error'=>'unabletodeletequestion'));
        }
        $questionInfo = $this->objWorksheetQuestions->getQuestion($question);
        if ($questionInfo == FALSE) {
            return $this->nextAction(NULL, array('error'=>'unabletodeletequestion'));
        }
        $this->objWorksheetQuestions->deleteQuestion($question);
        $this->objWorksheet->updateTotalMark($worksheet);
        return $this->nextAction('managequestions', array('id'=>$worksheet, 'message'=>'questiondeleted'));
    }
}

?>
