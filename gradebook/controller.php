<?php
/* -------------------- gradebook class extends controller ---------------- */
// security check - must be included in all scripts
if (!$GLOBALS['kewl_entry_point_run']) {
    die("You cannot view this page directly");
}

/**
 * controller class for the gradebook module
 * authors: Otim Samuel
 * date: 2005 05 06 (6th-May-2005)
 */

class gradebook extends controller {
    const PLAN_CSRF_SESSION_KEY = 'gradebook_assessment_plan_csrf';
    //user object - security and access rights validation
    public $objUser;

    //language object - multilingularity
    public $objLanguage;

    //logs
    public $objLog;

    //gradebook object
    public $objGradebook;

    //button object
    public $objButtons;

    //form object
    public $objForm;

    //heading
    public $heading;

    //heading object
    public $objHeading;

    //management of layer attributes <div tags etc
    public $objDiv;

    //context object
    public $objContext;

    //context code object
    public $contextCode;

    /**
     * initilization function - declaration of required objects
     */

    public function init() {
        $this->objUser=& $this->getObject('user','security');
        $this->objLanguage = & $this->getObject('language', 'language');
        $this->objForm = & $this->getObject('form','htmlelements');
        $this->objHeading =& $this->getObject('htmlheading','htmlelements');
        $this->objDiv =& $this->getObject('layer', 'htmlelements');
        $this->objPerm =& $this->getObject('contextcondition', 'contextpermissions');
        $this->objGradebook =& $this->getObject('gradebookfunctions','gradebook');
        //log activity from this class
        $this->objLog=$this->newObject('logactivity', 'logger');
        $this->objLog->log();
        $this->objButtons = & $this->getObject('navbuttons', 'navigation');
        // Get an instance of the context object
        $this->objContext = $this->getObject('dbcontext','context');
        //Get context code
        $this->contextCode = $this->objContext->getContextCode();
    }

    /**
     * dispatch() function for the gradebook module:
     * providing standard controlls for the module's logic and execution
     */

    public function dispatch($action) {
        //get the parameter from the querystring
        $action = $this->getParam("action", NULL);
        if ($this->contextCode == '') {
            return $this->nextAction(NULL, array('error'=>'notincontext'), '_default');
        }

        //assignment object
        $objAssignment = 0;
        $objAssignment = $this->getObject('dbassignment_old','assignment');
        $objAssignmentSubmit = 0;
        $objAssignmentSubmit = $this->getObject('dbassignmentsubmit_old','assignment');
        //essay object
        $objEssaytopics = 0;
        $objEssaytopics = $this->getObject('dbessay_topics','essay');
        $objEssaybook = 0;
        $objEssaybook = $this->getObject('dbessay_book','essay');
        //testadmin object
        $objTestadmin = 0;
        $objTestadmin = $this->getObject('dbtestadmin','mcqtests');
        $objTestresults = 0;
        $objTestresults = $this->getObject('dbresults','mcqtests');
        //worksheet object
        $objWorksheet = 0;
        $objWorksheet = $this->getObject('dbworksheet','worksheet');
        $objWorksheetresults = 0;
        $objWorksheetresults = $this->getObject('dbworksheetresults','worksheet');

        switch($action) {
            //default action
            case NULL:
                if($this->objUser->isAdmin() || $this->objPerm->isContextMember('Lecturers')) {                    
                    return "main_admin_tpl.php";
                } else {
                    return "main_user_tpl.php";
                }
                break;
            //show the course assessment-plan provider catalogue
            case 'assessmentPlan':
                if($this->objUser->isAdmin() || $this->objPerm->isContextMember('Lecturers')) {
                    $this->setVar('assessmentPlanCsrf', $this->issuePlanCsrf());
                    $this->setVar('assessmentPlanRows', $this->assessmentPlanRows());
                    return "assessment_plan_tpl.php";
                }
                return $this->nextAction(NULL, array('error'=>'noaccess'));
                break;
            case 'assessmentPlanAddProvider':
                if (!$this->mayManageAssessmentPlan()) {
                    return $this->nextAction(NULL, array('error'=>'noaccess'));
                }
                $providerKey = trim((string) $this->getParam('provider_key', ''));
                $registry = $this->getObject('assessmentproviderregistry', 'gradebook');
                $provider = $registry->get($providerKey);
                $adapter = $this->assessmentPlanProviderAdapter($registry, $provider);
                if ($provider === false || $adapter === false) {
                    return $this->nextAction('assessmentPlan', array('planerror'=>'invalidprovider'));
                }
                $this->setVar('assessmentPlanProvider', $provider);
                $this->setVar('assessmentPlanActivities', $adapter->listActivities($this->contextCode));
                $this->setVar('assessmentPlanCsrf', $this->issuePlanCsrf());
                return 'assessment_plan_add_provider_tpl.php';
                break;
            case 'assessmentPlanSaveProvider':
                if (!$this->mayManageAssessmentPlan()) {
                    return $this->nextAction(NULL, array('error'=>'noaccess'));
                }
                if (!$this->consumePlanCsrf((string) $this->getParam('csrf_token', ''))) {
                    return $this->nextAction('assessmentPlan', array('planerror'=>'invalidcsrf'));
                }
                return $this->saveProviderAssessmentPlanItem();
                break;
            case 'assessmentPlanRemoveItem':
                if (!$this->mayManageAssessmentPlan()) {
                    return $this->nextAction(NULL, array('error'=>'noaccess'));
                }
                if (!$this->consumePlanCsrf((string) $this->getParam('csrf_token', ''))) {
                    return $this->nextAction('assessmentPlan', array('planerror'=>'invalidcsrf'));
                }
                return $this->removeAssessmentPlanItem();
                break;
            case 'assessmentSheet':
                if (!$this->mayManageAssessmentPlan()) {
                    return $this->nextAction(NULL, array('error'=>'noaccess'));
                }
                $this->setVar('assessmentPlanCsrf', $this->issuePlanCsrf());
                $this->setVar('assessmentPlanRows', $this->assessmentPlanRows());
                return 'assessment_sheet_tpl.php';
                break;
            case 'assessmentSheetSave':
                if (!$this->mayManageAssessmentPlan()) {
                    return $this->nextAction(NULL, array('error'=>'noaccess'));
                }
                if (!$this->consumePlanCsrf((string) $this->getParam('csrf_token', ''))) {
                    return $this->nextAction('assessmentSheet', array('planerror'=>'invalidcsrf'));
                }
                return $this->saveAssessmentSheet();
                break;
            //view the details of the assessment
            case 'assessmentDetails':
                return "assignment_details_tpl.php";
                break;
            //view the grades based on assessment
            case 'viewByAssessment':
                return "view_assessment_tpl.php";
                break;
            //view the details based on assignments
            case 'assignmentDetails':
                return "assessment_details_tpl.php";
                break;
            //upload marks for offline assessments
            case 'uploadMarks':
                return "upload_tpl.php";
                break;
            //save marks for offline assessments
            case 'saveMarks':
            //get the submitted variables
                $assessmentName = 0;
                $assessmentName = $this->getParam("assessmentName", NULL);
                $assessmentType = 0;
                $assessmentType = $this->getParam("assessmentType", NULL);
                $percentFinalMark = 0;
                $percentFinalMark = $this->getParam("percentFinalMark", NULL);
                $numberStudents = 0;
                $numberStudents = $this->getParam("numberStudents", NULL);
                $contextCode = 0;
                $contextCode = $this->getParam("contextCode", NULL);
                $closingDate = 0;
                $closingDate = $this->getParam("closingDate", NULL);
                $description = 0;
                $description = $this->getParam("description", NULL);

                switch($assessmentType) {
                    case 'Essays':
                        //insert into tbl_essay_topics
                        $fields = array();
                        $fields['name'] = $assessmentName;
                        $fields['percentage'] = $percentFinalMark;
                        $fields['userid'] = $this->objUser->userId();
                        $fields['context'] = $contextCode;
                        $fields['closing_date'] = $closingDate;
                        $fields['description'] = $description;
                        $id = $objEssaytopics->addTopic($fields);
                        break;
                    case 'MCQ Tests':
                        //insert into tbl_tests
                        $fields = array();
                        $fields['name'] = $assessmentName;
                        $fields['percentage'] = $percentFinalMark;
                        $fields['userid'] = $this->objUser->userId();
                        $fields['context'] = $contextCode;
                        $fields['closingdate'] = $closingDate;
                        $fields['description'] = $assessmentName;
                        $fields['status'] = "closed";
                        $fields['testtype'] = "offline";
                        $id = $objTestadmin->addTest($fields);
                        break;
                    case 'Online Worksheets':
                        //insert into tbl_worksheet
                        $fields = array();
                        $fields['name'] = $assessmentName;
                        $fields['percentage'] = $percentFinalMark;
                        $fields['userid'] = $this->objUser->userId();
                        $fields['context'] = $contextCode;
                        $fields['closing_date'] = $closingDate;
                        $fields['description'] = $description;
                        $id = $objWorksheet->addWorksheet($fields);
                        break;
                    case 'Assignments':
                    default:                        
                        //insert into tbl_assignment
                        $fields = array();
                        $fields['name'] = $assessmentName;
                        $fields['percentage'] = $percentFinalMark;
                        $fields['userid'] = $this->objUser->userId();
                        $fields['context'] = $contextCode;
                        $fields['closing_date'] = $closingDate;
                        $fields['description'] = $description;
                        $id = $objAssignment->addAssignment($fields);
                        break;
                }
                $totalMarkMCQ = 0;
                for($i=1;$i<=$numberStudents;$i++) {
                    //get the userId
                    $userId = 0;
                    $userId = $this->getParam("userid".$i, NULL);

                    //get the student mark
                    $studentMark = 0;
                    $studentMark = $this->getParam("studentMark".$i, NULL);

                    switch($assessmentType) {
                        case 'Essays':
                            //insert into tbl_essay_book
                            $fields = array();
                            $fields['topicid'] = $id;
                            $fields['studentid'] = $userId;
                            $fields['context'] = $contextCode;
                            $fields['mark'] = $studentMark;
                            $objEssaybook->bookEssay($fields);
                            break;
                        case 'MCQ Tests':
                            //insert into tbl_test_results
                            $fields = array();
                            $fields['testid'] = $id;
                            $fields['studentid'] = $userId;
                            $fields['mark'] = $studentMark;
                            $totalMarkMCQ += $studentMark;
                            $objTestresults->addResult($fields);
                            break;
                        case 'Online Worksheets':
                            //insert into tbl_worksheet_results
                            $fields = array();
                            $fields['worksheet_id'] = $id;
                            $fields['userid'] = $userId;
                            $fields['mark'] = $studentMark;
                            $objWorksheetresults->addResult($fields);
                            break;
                        case 'Assignments':
                        default:
                            //insert into tbl_assignment_submit
                            $fields = array();
                            $fields['assignmentid'] = $id;
                            $fields['userid'] = $userId;
                            $fields['mark'] = $studentMark;
                            $objAssignmentSubmit->addSubmit($fields);
                            break;
                    }
                }
                //Store MCQ total mark
                $objTestadmin->setTotal($id, $totalMarkMCQ);
                return $this->nextAction('viewByAssessment',array('dropdownAssessments'=>$assessmentType));
                break;
        }
    }

    private function mayManageAssessmentPlan()
    {
        return $this->objUser->isAdmin() || $this->objPerm->isContextMember('Lecturers');
    }

    private function issuePlanCsrf()
    {
        $token = bin2hex(random_bytes(32));
        $this->setSession(self::PLAN_CSRF_SESSION_KEY, $token);
        return $token;
    }

    private function consumePlanCsrf($token)
    {
        $expected = (string) $this->getSession(self::PLAN_CSRF_SESSION_KEY, '');
        $this->setSession(self::PLAN_CSRF_SESSION_KEY, '');
        return $expected !== '' && hash_equals($expected, $token);
    }

    /**
     * A selectable provider must declare activity_selection and expose the
     * two read-only adapter methods. This is the single add-button contract.
     */
    private function assessmentPlanProviderAdapter($registry, $provider)
    {
        if (!is_array($provider)
            || !in_array('activity_selection', (array) $provider['capabilities'], true)) {
            return false;
        }
        $adapter = $registry->adapter($provider['key']);
        return is_object($adapter)
            && is_callable(array($adapter, 'listActivities'))
            && is_callable(array($adapter, 'getActivity')) ? $adapter : false;
    }

    private function saveProviderAssessmentPlanItem()
    {
        $providerKey = trim((string) $this->getParam('provider_key', ''));
        $activityId = trim((string) $this->getParam('activity_id', ''));
        $registry = $this->getObject('assessmentproviderregistry', 'gradebook');
        $provider = $registry->get($providerKey);
        $adapter = $this->assessmentPlanProviderAdapter($registry, $provider);
        if ($activityId === '' || $provider === false || $adapter === false) {
            return $this->nextAction('assessmentPlan', array('planerror'=>'invaliditem'));
        }
        $activity = $adapter->getActivity($this->contextCode, $activityId);
        if (!is_array($activity) || empty($activity['name'])) {
            return $this->nextAction('assessmentPlanAddProvider', array('provider_key'=>$providerKey, 'planerror'=>'invaliditem'));
        }

        $plans = $this->getObject('dbgradebookassessmentplans', 'gradebook');
        $items = $this->getObject('dbgradebookassessmentplanitems', 'gradebook');
        $planId = $plans->ensureForContext($this->contextCode, $this->objUser->userId());
        if (!$planId || $items->findByActivity($planId, $providerKey, $activityId)) {
            return $this->nextAction('assessmentPlan', array('planerror'=>'duplicateitem'));
        }

        $saved = $items->addItem(array(
            'plan_id' => $planId,
            'provider_key' => $providerKey,
            'provider_module' => $provider['module_id'],
            'activity_id' => $activityId,
            'name' => $activity['name'],
            'weight' => '0.000',
            'include_in_course_mark' => 'Y',
            'required_for_completion' => 'N',
            'result_rule' => 'latest_completed',
            'opening_enabled' => 'N',
            'opening_date' => null,
            'closing_enabled' => 'N',
            'closing_date' => null,
            'created_by' => $this->objUser->userId()
        ));
        return $this->nextAction('assessmentPlan', $saved ? array('planmessage'=>'saved') : array('planerror'=>'savefailed'));
    }

    private function removeAssessmentPlanItem()
    {
        $itemId = trim((string) $this->getParam('item_id', ''));
        $plans = $this->getObject('dbgradebookassessmentplans', 'gradebook');
        $plan = $plans->findForContext($this->contextCode);
        $items = $this->getObject('dbgradebookassessmentplanitems', 'gradebook');
        $removed = $plan && $itemId !== '' && $items->removeItem($plan['id'], $itemId);
        return $this->nextAction('assessmentPlan', $removed ? array('planmessage'=>'removed') : array('planerror'=>'removefailed'));
    }

    private function saveAssessmentSheet()
    {
        $plans = $this->getObject('dbgradebookassessmentplans', 'gradebook');
        $plan = $plans->findForContext($this->contextCode);
        if (!$plan) {
            return $this->nextAction('assessmentSheet');
        }
        $submitted = $this->getParam('weight', array());
        if (!is_array($submitted)) {
            return $this->nextAction('assessmentSheet', array('planerror'=>'invalidweights'));
        }
        $items = $this->getObject('dbgradebookassessmentplanitems', 'gradebook');
        foreach ($submitted as $itemId => $weight) {
            if (!preg_match('/^[A-Za-z0-9_]+$/', (string) $itemId) || !is_numeric($weight)
                || (float) $weight < 0 || (float) $weight > 100) {
                return $this->nextAction('assessmentSheet', array('planerror'=>'invalidweights'));
            }
            if (!$items->saveWeight($plan['id'], $itemId, $weight)) {
                return $this->nextAction('assessmentSheet', array('planerror'=>'savefailed'));
            }
        }
        return $this->nextAction('assessmentSheet', array('planmessage'=>'weights_saved'));
    }

    /**
     * Build the learner view from the Assessment Plan and provider-owned results.
     * Negative legacy sentinel values are never grades.
     */
    public function studentAssessmentRows($userId)
    {
        $registry = $this->getObject('assessmentproviderregistry', 'gradebook');
        $rows = array();
        foreach ($this->assessmentPlanRows() as $planRow) {
            $item = $planRow['item'];
            $result = array('status' => 'not_attempted', 'mark_percent' => null);
            $adapter = $registry->adapter($item['provider_key']);
            if (is_object($adapter) && is_callable(array($adapter, 'getStudentResult'))) {
                $candidate = $adapter->getStudentResult(
                    $this->contextCode,
                    $item['activity_id'],
                    $userId,
                    isset($item['result_rule']) ? $item['result_rule'] : 'latest_completed'
                );
                if (is_array($candidate) && !empty($candidate['status'])) {
                    $result = $candidate;
                }
            }

            $weight = !isset($item['include_in_course_mark'])
                || strtoupper((string) $item['include_in_course_mark']) === 'Y'
                ? max(0.0, (float) $item['weight']) : 0.0;
            $mark = isset($result['mark_percent']) && is_numeric($result['mark_percent'])
                ? max(0.0, min(100.0, (float) $result['mark_percent'])) : null;
            $closingDate = null;
            if (isset($item['closing_enabled']) && strtoupper((string) $item['closing_enabled']) === 'Y'
                && !empty($item['closing_date']) && strtotime((string) $item['closing_date']) !== false) {
                $closingDate = $item['closing_date'];
            }

            $rows[] = array(
                'title' => $planRow['title'],
                'provider' => $planRow['provider'],
                'closing_date' => $closingDate,
                'weight' => $weight,
                'status' => $result['status'],
                'mark_percent' => $mark,
                'weighted_mark' => $mark === null ? null : ($mark * $weight / 100)
            );
        }
        return $rows;
    }

    private function assessmentPlanRows()
    {
        $plans = $this->getObject('dbgradebookassessmentplans', 'gradebook');
        $plan = $plans->findForContext($this->contextCode);
        if (!$plan) {
            return array();
        }
        $items = $this->getObject('dbgradebookassessmentplanitems', 'gradebook')->getForPlan($plan['id']);
        $registry = $this->getObject('assessmentproviderregistry', 'gradebook');
        $rows = array();
        foreach ($items as $item) {
            $provider = $registry->get($item['provider_key']);
            $adapter = $provider ? $registry->adapter($item['provider_key']) : false;
            $activity = is_object($adapter) ? $adapter->getActivity($this->contextCode, $item['activity_id']) : false;
            $rows[] = array(
                'item' => $item,
                'title' => is_array($activity) && isset($activity['name']) ? $activity['name'] : $item['name'],
                'provider' => $provider ? $provider['label'] : $item['provider_module'],
                'adapter' => $adapter,
                'classification' => is_array($activity) && !empty($activity['classification']) ? $activity['classification'] : 'unclassified',
                'available' => is_array($activity)
            );
        }
        return $rows;
    }
}
?>
