<?php
// security check - must be included in all scripts
if (!$GLOBALS['kewl_entry_point_run']){
    die('You cannot view this page directly');
}
// end security check

/**
* Controller class for the Essay Management module.
* Lecturers can create, edit and delete topics.
* They can create, edit and delete essays
* within each topic and download students'
* submitted essays for marking. They can mark
* essays and upload marked essays.
*
* @author Megan Watson
* @author Jeremy O'Connor
* @copyright (c) 2004, 2010 Avoir
* @package essay
* @version $Id$
*/

/**
* Controller class for the Essay Management module.
* @package essay
*/

class essaymanagementbase extends controller
{
    /**
    * Initialization method.
    */
    public function init()
    {
        $this->objConfig = $this->newObject('altconfig', 'config');
        $this->dbtopic = $this->getObject('dbessay_topics', 'essay');
        $this->dbessays = $this->getObject('dbessays', 'essay');
        $this->dbbook = $this->getObject('dbessay_book', 'essay');
        $this->objDefaultEssayRubric = $this->getObject('essaydefaultrubric', 'essay');
        $this->objAiMarkingJobs = $this->getObject('dbessayaimarkingjobs', 'essay');
        $this->aiMarkingAvailable = $this->getObject('essayaimarker', 'essay')->isAvailable();
        // Get instances of the html elements:
        $this->loadclass('htmltable', 'htmlelements');
        $this->loadClass('checkbox', 'htmlelements');
        $this->loadclass('form', 'htmlelements');
        $this->loadClass('layer', 'htmlelements');
        $this->loadClass('link', 'htmlelements');
        $this->loadClass('textinput', 'htmlelements');
        $this->loadClass('button', 'htmlelements');
        $this->loadClass('textarea', 'htmlelements');
        $this->loadClass('iframe', 'htmlelements');
        $this->loadClass('htmlHeading', 'htmlelements');
        $this->objIcon = $this->getObject('geticon', 'htmlelements');
        $this->objDate = $this->newObject('datepicker', 'htmlelements');
		$this->objFile = $this->newObject('upload', 'filemanager');
        // Get an instance of the confirmation object
        $this->objConfirm = $this->getObject('confirm', 'utilities');
        $this->objTimeAndDate = $this->getObject('timeanddateservice','timeanddate-service');
        // Get an instance of the language object
        $this->objLanguage = $this->getObject('language', 'language');
        // Get an instance of the user object
        $this->objUser = $this->getObject('user', 'security');
        // Get an instance of the context object
        $this->objContext = $this->getObject('dbcontext', 'context');
        $this->objContextGroups = $this->getObject('managegroups', 'contextgroups');
        $this->objHelp = $this->newObject('helplink', 'help');
        $this->objModules = $this->newObject('modules', 'modulecatalogue');
        //if(!$this->objModules->checkIfRegistered('Essay Management','essayadmin')){
        //    return $this->nextAction('notregistered', array(), 'redirect');
        //}
        // Check if the assignment module is registered and can be linked to.
        $this->assignment = $this->objModules->checkIfRegistered('assignment');
        if (!$this->objModules->checkIfRegistered('rubric')) {
            $this->rubric = FALSE;
        } else {
            $this->rubric = TRUE;
            $this->objRubric = $this->getObject('dbrubricassessments', 'rubric');
        }
        // Log this call if registered
        if ($this->objModules->checkIfRegistered('logger', 'logger')){
            //Get the activity logger class
            $this->objLog = $this->newObject('logactivity', 'logger');
            //Log this module call
            $this->objLog->log();
        }
        // Load the activity Streamer
        if (!$this->objModules->checkIfRegistered('activitystreamer'))
        {
        	$this->eventsEnabled = FALSE;
        } else {
        	$this->eventsEnabled = TRUE;
        	$this->objActivityStreamer = $this->getObject('activityops', 'activitystreamer');
        	$this->eventDispatcher->addObserver ( array ($this->objActivityStreamer, 'postmade' ) );
        }
    }

    /**
    * The standard dispatch method for the module.
    * @return string The template
    */
    public function dispatch($action)
    {
        $this->setVar('pageSuppressXML',true);
		/**
		* management of zip files, added 27/mar/06
		* check if the essayadmin dir has been created
		* @author: otim samuel, sotim@dicts.mak.ac.ug
		*/
        //"usrfiles/essayadmin/"
		$essayadmindir = $this->objConfig->getcontentBasePath().'essay/';
		if(!is_dir($essayadmindir)) {
			mkdir($essayadmindir, 0777);
		}
		$this->setVar('essayadmindir',$essayadmindir);
		/*
		* $essayadminDownloadLink is currently made up of
		* http://nextgen.mak.ac.ug/index.php?module=essayadmin
		* or the equivalent. required is to remove
		* index.php?module=essayadmin or its equivalent
		* $_SERVER['QUERY_STRING'] contains everthing after the ?
		* hence by appending this variable to index.php? and running
		* ereg_replace("index.php\?".$_SERVER['QUERY_STRING'],"",$essayadminDownloadLink)
		* add a \? cause the ? is taken as a regular expression
		* should give us http://nextgen.mak.ac.ug/ which can then be appended to
		* $essayadmindir and the required download file for an accurate download link
		*/
		$essayadminDownloadLink =
		    $this->objConfig->getsiteRoot()
		    .$this->objConfig->getcontentPath()
		    .'essay/';
		$this->setVar('essayadminDownloadLink',$essayadminDownloadLink);
		//remove all zip files older than 24hrs, or 86,400 seconds
		//$this->objDbZip->deleteOldFiles();
        // Get user details
        $this->userId = $this->objUser->userId();
        $this->user = $this->objUser->fullname();
        // Check if in context, and get code & title
        if ($this->objContext->isInContext()) {
            $incontext = TRUE;
            $this->contextcode = $this->objContext->getContextCode();
            $this->context = $this->objContext->getTitle();
        } else {
            $incontext = FALSE;
        }

        if (!$this->objUser->isCourseAdmin($this->contextcode)) {
            return 'manage_noaccess_tpl.php';
        }

        // Set variable references in templates
        $this->setVarByRef('contextcode', $this->contextcode);
        $this->setVarByRef('context', $this->context);

        //$topicid=$this->getParam('id');
        switch($action){
        case 'addtopic':
            // Add a new topic
            $heading = $this->objLanguage->languageText('mod_essayadmin_newtopicarea','essayadmin');
            $data = array();
            $this->setVarByRef('heading', $heading);
            $this->setVarByRef('data', $data);
            $this->setLayoutTemplate('essay_management_layout_tpl.php');
            return 'manage_topic_tpl.php';
        //break;
        // edit a topic
        case 'edit':
        case 'edittopic':
            // get topic id
            $id=$this->getParam('id');
            // get topic details
            $data = $this->dbtopic->getTopic($id);
            $heading = $this->objLanguage->languageText('mod_essayadmin_edittopicarea','essayadmin').': '.$data[0]['name'];
            $this->setVarByRef('heading',$heading);
            $this->setVarByRef('data',$data);
            $this->setVar('defaultRubric', $this->objDefaultEssayRubric->getStructuredRubric());
            $this->setLayoutTemplate('essay_management_layout_tpl.php');
            return 'manage_topic_tpl.php';
        //break;
        // save topic
        case 'savetopic':
            $id = $this->getParam('id', NULL);
            $fields = array();
            $fields['context']=$this->contextcode;
            $fields['name']=trim((string)$this->getParam('topicarea', ''));
            $fields['description']=$this->getParam('description', '');
            $fields['instructions']=$this->getParam('instructions', '');
            $closingDate=trim((string)$this->getParam('closing_date',''));
            $closingTime=trim((string)$this->getParam('closing_time',''));
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/',$closingDate)
                || !preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/',$closingTime)) {
                return $this->nextAction($id ? 'edittopic' : 'addtopic',array('id'=>$id,'error'=>'invalidclosingdate'));
            }
            $localClosing=$closingDate.' '.$closingTime.':00';
            $closingInstant=$this->objTimeAndDate->parseLocal($localClosing);
            if ($closingInstant === null) {
                return $this->nextAction($id ? 'edittopic' : 'addtopic',array('id'=>$id,'error'=>'invalidclosingdate'));
            }
            $fields['closing_date']=$this->objTimeAndDate->toStorage($closingInstant);
            if ($fields['name'] === '') {
                return $this->nextAction($id ? 'edittopic' : 'addtopic', array('id'=>$id, 'error'=>'topicrequired'));
            }
            $force = $this->getParam('force',NULL);
            $bypass = $this->getParam('bypass',NULL);
            $fields['forceone'] = ($force == 'on')?'1':'0';
            $fields['bypass'] = ($bypass == 'on')?'1':'0';
            if(is_null($id)){
                $this->dbtopic->addTopic($fields);
                $id=$this->dbtopic->getLastInsertId();
            } else {
                $this->dbtopic->addTopic($fields,$id);
            }
            // set confirmation message
            $message = $this->objLanguage->languageText('mod_essayadmin_confirmtopicarea','essayadmin');
            $this->setSession('confirm', $message);
            //add to activity log
            if($this->eventsEnabled)
            {
                $message = $this->objUser->getsurname()." ".$this->objLanguage->languageText('mod_essayadmin_hasaddedessay', 'essayadmin').": ".$fields['name']." ".$this->objLanguage->languageText('mod_essayadmin_in', 'essayadmin')." ".$this->objContext->getContextCode();
                $this->eventDispatcher->post(
                    $this->objActivityStreamer,
                    "context",
                    array(
                        'title'=>$message,
                		'link'=>$this->uri(array()),
                		'contextcode'=>$this->objContext->getContextCode(),
                		'author'=>$this->objUser->fullname(),
                		'description'=>$message)
                );
            }
            return $this->nextAction('view', array('id' => $id, 'confirm' => 'yes'));
            //}
            //$this->nextAction('');
        break;
        // view essays within a topic
        case 'view':
            // get id of topic being viewed
            $topicAreaId = $this->getParam('id');
            // get table display[0]['essayid']
            //$content=; //$essays,$topic
            $this->setVar('content', $this->manageRenderEssays($topicAreaId));
            $this->setLayoutTemplate('essay_management_layout_tpl.php');
            return 'manage_essay_tpl.php';
        //break;
        // delete a topic
        case 'delete':
        case 'deletetopic':
            // get topic id
            $topicAreaId=$this->getParam('id');
            $this->dbtopic->deleteTopic($topicAreaId);
            //$this->deleteEssay($id);
            $rows=$this->dbessays->getEssays($topicAreaId);
            if(!empty($rows)){
                foreach($rows as $item){
                    $essayId = $item['id'];
                    $this->dbessays->deleteEssay($essayId);
                    // delete bookings on essay
                    $this->dbbook->deleteBooking(NULL, "WHERE topicid='{$topicAreaId}' AND essayid='{$essayId}'");
                }
            }
            $back=$this->getParam('back');
            if($back){
                header("Location: ".$this->uri(array('action'=>'viewbyletter'),'assignmentadmin'));
                return NULL;
            }else{
                return $this->nextAction(NULL);
            }
        //break;
        case 'addessay':
            // Add essay
            // get topic id
            $topicAreaId=$this->getParam('id');
            // get topic data
            $topic=$this->dbtopic->getTopic($topicAreaId,'name, id');
            $heading = $this->objLanguage->languageText('mod_essayadmin_addessay','essayadmin').':&nbsp;'.$topic[0]['name'];
            $data=array();
            $this->setVarByRef('topicid',$topic[0]['id']);
            $this->setVarByRef('topicname',$topic[0]['name']);
            $this->setVarByRef('heading',$heading);
            $this->setVarByRef('data',$data);
            $this->setVar('defaultRubric', $this->objDefaultEssayRubric->getStructuredRubric());
            $this->setLayoutTemplate('essay_management_layout_tpl.php');
            return 'manage_addeditessay_tpl.php';
        //break;
        case 'editessay':
            // edit essay
            // get id of topic
            $topicAreaId=$this->getParam('id');
            // get id of essay
            $essay=$this->getParam('essay');
            // get topic data
            $topic=$this->dbtopic->getTopic($topicAreaId,'id, name');
            // get essay data
            $data=$this->dbessays->getEssay($essay);
            $heading = $this->objLanguage->languageText('mod_essayadmin_editessay','essayadmin').':&nbsp;'.$topic[0]['name'];
            $this->setVarByRef('topicid',$topic[0]['id']);
            $this->setVarByRef('topicname',$topic[0]['name']);
            $this->setVarByRef('heading',$heading);
            $this->setVarByRef('data',$data);
            $this->setLayoutTemplate('essay_management_layout_tpl.php');
            return 'manage_addeditessay_tpl.php';
        //break;
        case 'saveessay':
            // save essay
            //$confirm = NULL;
            //if($this->getParam('save')==$this->objLanguage->languageText('word_save')){
            $topicAreaId = $this->getParam('id', '');
            $id=$this->getParam('essay');

            $fields=array();
            $fields['topicid']=$topicAreaId;
            $fields['topic']=trim((string)$this->getParam('essaytopic', ''));
            $fields['notes']=$this->getParam('notes', '');
            $fields['model_essay']=$this->getParam('model_essay', '');
            $topic = $this->dbtopic->getTopic($topicAreaId);
            if ($fields['topic'] === '' || empty($topic)
                || (string)$topic[0]['context'] !== (string)$this->contextcode) {
                return $this->nextAction('addessay', array('id'=>$topicAreaId, 'error'=>'invalid'));
            }
            $this->dbessays->addEssay($fields, $id !== '' ? $id : NULL);
            // set confirmation message
            $message = $this->objLanguage->languageText('mod_essayadmin_confirmessay', 'essayadmin');
            $this->setSession('confirm', $message);
            //$confirm = ;
            //}
            return $this->nextAction('view',array('id'=>$topicAreaId, 'confirm' => 'yes'));
        //break;
        case 'deleteessay':
            // delete an essay
            // get topic id
            $topicAreaId=$this->getParam('id');
            // get essay id
            $essayId=$this->getParam('essay');
            //$this->deleteEssay($topicAreaId, $id);
            $this->dbessays->deleteEssay($essayId);
            // delete bookings on essay
            $this->dbbook->deleteBooking(NULL,"WHERE topicid='{$topicAreaId}' and essayid='{$essayId}'");
            return $this->nextAction('view',array('id'=>$topicAreaId));
        //break;
        case 'mark':
        //case 'viewmarktopic':
        case 'marktopic':
            // list student essay submissions
            // get topic id
            $topicAreaId=$this->getParam('id');
            $topicdata=$this->dbtopic->getTopic($topicAreaId,'id, name, closing_date');
            // get booked essays in topic
            $data=$this->dbbook->getBooking("WHERE topicid='{$topicAreaId}'");
            // get essay titles and student names for each booked essay
            foreach($data as $key=>$item){
                $essay=$this->dbessays->getEssay($item['essayid'],'topic');
                $data[$key]['essay']=$essay[0]['topic'];
                //$student=$this->objUser->fullname($item['studentid']);
                //$data[$key]['student']=$student;//[0]['fullname'];
                $data[$key]['studentNo']=$this->objUser->getStaffNumber($item['studentid']); //[0]['fullname'];
                $data[$key]['student']=$this->objUser->fullname($item['studentid']); //$student;//[0]['fullname'];
            }
            $this->setVar('heading', $this->objLanguage->code2Txt('mod_essayadmin_submittedessaysintopicarea','essayadmin', array('TOPICAREA'=>$topicdata[0]['name'])));
            $this->setVarByRef('topicdata',$topicdata);
            $this->setVarByRef('data',$data);
            $this->setVar('aiMarkingAvailable', $this->aiMarkingAvailable);
            $this->setVar('aiMarkingJobs', $this->objAiMarkingJobs->listForTopic($topicAreaId,$this->contextcode,$this->userId,$this->objUser->isAdmin()));
            $this->setVar('aiBatchToken', $this->getObject('nativeauthwebcomposition','security')->build()['csrf']->issue('essay_ai_batch_marking'));
            $this->setLayoutTemplate('essay_management_layout_tpl.php');
            return 'manage_mark_essays_tpl.php';
        case 'aiassistmark':
            $stack=$this->getObject('nativeauthwebcomposition','security')->build();$book=(string)$this->getParam('book','');
            if(!$this->aiMarkingAvailable||strtoupper((string)($_SERVER['REQUEST_METHOD']??''))!=='POST'||!$stack['csrf']->consume('essay_ai_marking',(string)$this->getParam('csrf_token',''))){return $this->nextAction(null);}
            $booking=$this->dbbook->getRow('id',$book);
            if(!is_array($booking)||(string)$booking['context']!==(string)$this->contextcode||empty($booking['submitdate'])){return $this->nextAction(null,array('error'=>'invalidsubmission'));}
            $existing=$this->objAiMarkingJobs->getLatestCompleted($book,$this->contextcode,$this->userId,$this->objUser->isAdmin());
            if(is_array($existing)){return $this->nextAction('aimarkingjob',array('id'=>$existing['id']));}
            return $this->nextAction('aimarkingjob',array('id'=>$this->objAiMarkingJobs->enqueue($this->contextcode,$this->userId,$book)));
        case 'aibatchmark':
            $stack=$this->getObject('nativeauthwebcomposition','security')->build();$topic=(string)$this->getParam('id','');
            if(!$this->aiMarkingAvailable||strtoupper((string)($_SERVER['REQUEST_METHOD']??''))!=='POST'||!$stack['csrf']->consume('essay_ai_batch_marking',(string)$this->getParam('csrf_token',''))){return $this->nextAction(null);}
            $topicRows=$this->dbtopic->getTopic($topic);
            if(empty($topicRows[0])||(string)$topicRows[0]['context']!==(string)$this->contextcode){return $this->nextAction(null);}
            foreach((array)$this->dbbook->getBooking("WHERE topicid='".addslashes($topic)."' AND context='".addslashes($this->contextcode)."'") as $booking){if(!empty($booking['submitdate'])&&($booking['mark']===null||$booking['mark']==='')){$this->objAiMarkingJobs->enqueue($this->contextcode,$this->userId,$booking['id']);}}
            return $this->nextAction('marktopic',array('id'=>$topic,'message'=>'aibatchqueued'));
        case 'aimarkingjob':
            $job=$this->objAiMarkingJobs->getOwned($this->getParam('id',''),$this->contextcode,$this->userId,$this->objUser->isAdmin());
            if(!is_array($job)){return $this->nextAction(null);}
            if($job['status']==='completed'){
                $booking=$this->dbbook->getRow('id',$job['booking_id']);if(!is_array($booking)){return $this->nextAction(null);}
                $this->setVar('aiSuggestion',$job['suggestion']);
                $this->setSession('mark',(string)($job['suggestion']['mark']??0));$this->setSession('comment',(string)($job['suggestion']['feedback']??''));
                return $this->nextAction('upload',array('id'=>$booking['topicid'],'book'=>$booking['id'],'ai_job'=>$job['id']));
            }
            $this->setVar('aiMarkingJob',$job);$this->setLayoutTemplate('essay_management_layout_tpl.php');return 'ai_marking_progress_tpl.php';
        case 'download':
            $this->setVar('fileId', $this->getParam('fileid'));
            $this->setPageTemplate(NULL);
            $this->setLayoutTemplate(NULL);
            return 'manage_download_tpl.php';
        //break;
        case 'upload':
            // Upload essay
            // Get topic area ID
            $topic=$this->getParam('id');
            // Get book ID
            $id=$this->getParam('book');
            // Get rubric ID
            $rubric=$this->getParam('rubric');
            //
            $message = $this->getSession('message','');
            $mark = $this->getSession('mark','0');
            $comment = $this->getSession('comment','');
            $this->unsetSession('message');
            $this->unsetSession('mark');
            $this->unsetSession('comment');
            $this->setVar('message', $message);
            $this->setVar('mark', $mark);
            $this->setVar('comment', $comment);
            $recovery=$this->objAiMarkingJobs->getLatestCompleted($id,$this->contextcode,$this->userId,$this->objUser->isAdmin());
            $this->setVar('aiSuggestion',is_array($recovery)?$recovery['suggestion']:array());
            $this->setVar('aiMarkingAvailable',$this->aiMarkingAvailable);
            $this->setVar('aiMarkingToken',$this->getObject('nativeauthwebcomposition','security')->build()['csrf']->issue('essay_ai_marking'));
            //
            $this->setVarByRef('heading', $this->objLanguage->languageText('mod_essayadmin_markessay','essayadmin'));
            $this->setVar('topic', $topic);
            $this->setVar('book', $id);
            $this->setvar('rubric', $rubric);
            $this->setLayoutTemplate('essay_management_layout_tpl.php');
            return 'manage_upload_tpl.php';
        //break;
        case 'uploadsubmit':
            // Mark an essay and upload marked essay
            // Get topic ID
            $topic = $this->getParam('id');
            // Get book ID
            $book = $this->getParam('book');
            $booking = $this->dbbook->getBooking("WHERE id='".addslashes($book)."' AND topicid='".addslashes($topic)."' AND context='".addslashes($this->contextcode)."'");
            if (empty($booking)) {
                return $this->nextAction(null, array('error'=>'invalidsubmission'));
            }
            $mark=$this->getParam('mark', '');
            $comment=$this->getParam('comment', '');
            $useAiSuggestion=(string)$this->getParam('use_ai_suggestion','')==='1';
            $lecturerAdjustment=(int)$this->getParam('lecturer_adjustment',0);
            $integrityAdjustment=$useAiSuggestion?$lecturerAdjustment:0;
            $integrityReason=trim((string)$this->getParam('integrity_reason',''));
            $aiBaseMark=null;
            if ($useAiSuggestion) {
                $recovery=$this->objAiMarkingJobs->getLatestCompleted($book,$this->contextcode,$this->userId,$this->objUser->isAdmin());
                if (is_array($recovery) && isset($recovery['suggestion']['mark']) && is_numeric($recovery['suggestion']['mark'])) {
                    $aiBaseMark=max(0,min(100,(int)$recovery['suggestion']['mark']));
                }
            }
            $message = null;
            $fileDetails = null;
            if ($useAiSuggestion && $aiBaseMark===null) {
                $message = 'The retained AI suggestion could not be found. Review the submission and enter a final mark manually.';
            } else if (!$useAiSuggestion && (!is_numeric($mark) || (float)$mark < 0 || (float)$mark > 100)) {
                $message = 'Enter a mark from 0 to 100.';
            } else if ($lecturerAdjustment < -100 || $lecturerAdjustment > 100) {
                $message = 'The lecturer adjustment must be from -100 to 100 percentage points.';
            } else if ($lecturerAdjustment !== 0 && $integrityReason === '') {
                $message = 'Record the reason for the lecturer adjustment.';
            } else {
                if (!empty($_FILES['file']['name'])) {
                    $fileDetails = $this->objFile->uploadFile('file');
                }
            }

            if ($message !== null) {
                // Preserve the validation message set above.
            } else if ($fileDetails === FALSE){
                $message = $this->objLanguage->languageText('mod_essayadmin_uploadfailureunknown', 'essayadmin');
            } else if (is_array($fileDetails) && empty($fileDetails['success'])) {
                switch ($fileDetails['reason']) {
                    case 'bannedfile':
                        $reason = $this->objLanguage->languageText('mod_essayadmin_fileupload_bannedfile', 'essayadmin');
                        break;
                    case 'partialuploaded':
                        $reason = $this->objLanguage->languageText('mod_essayadmin_fileupload_partialuploaded', 'essayadmin');
                        break;
                    case 'nouploadedfileprovided':
                        $reason = $this->objLanguage->languageText('mod_essayadmin_fileupload_nouploadedfileprovided', 'essayadmin');
                        break;
                    case 'doesnotmeetextension':
                        $reason = $this->objLanguage->languageText('mod_essayadmin_fileupload_doesnotmeetextension', 'essayadmin');
                        break;
                    case 'needsoverwrite':
                        $reason = $this->objLanguage->languageText('mod_essayadmin_fileupload_needsoverwrite', 'essayadmin');
                        break;
                    case 'filecouldnotbesaved':
                        $reason = $this->objLanguage->languageText('mod_essayadmin_fileupload_filecouldnotbesaved', 'essayadmin');
                        break;
                    default:
                        $reason = $this->objLanguage->languageText('mod_essayadmin_fileupload_unknownreason', 'essayadmin');
                }
                $message = $this->objLanguage->languageText('mod_essayadmin_uploadfailure', 'essayadmin')
                   .":&nbsp;" . $reason;
            } else {
                $baseMark=$useAiSuggestion?$aiBaseMark:(int)$mark;
                $finalMark=max(0,min(100,$baseMark+$integrityAdjustment));
                $fields = array('mark'=>$finalMark, 'comment'=>$comment, 'integrity_adjustment'=>$integrityAdjustment, 'integrity_reason'=>$integrityReason);
                if (!empty($fileDetails['fileid'])) { $fields['lecturerfileid']=$fileDetails['fileid']; }
                $this->dbbook->bookEssay($fields, $book);
                // display success message
                $message = NULL;
            	}
            if (!is_null($message)) {
    			$this->setSession('message',$message);
    			$this->setSession('mark',$mark);
    			$this->setSession('comment',$comment);
                return $this->nextAction('upload', array('id'=>$topic, 'book'=>$book));
            }
            else {
                return $this->nextAction('marktopic', array('id'=>$topic));
            }
        default:
            $this->setVar('content', $this->manageRenderTopics());
            $this->setLayoutTemplate('essay_management_layout_tpl.php');
            return 'manage_essay_tpl.php';
        }
        return $template;
    }

    /**
    * Renders the topics.
    * @return string Rendered content.
    */
    function manageRenderTopics()
    {
        // Get topic data
        $rs = $this->dbtopic->getTopic(NULL, NULL, "context='{$this->contextcode}'");
        $topics = array();
        if(!empty($rs)){
            foreach($rs as $key=>$item){
                $bookings = $this->dbbook->getBooking(
                    "WHERE topicid='".$item['id']."' AND context='".addslashes($this->contextcode)."'",
                    "SUM(CASE WHEN submitdate IS NOT NULL THEN 1 ELSE 0 END) AS submitted, COUNT(mark) AS marked"
                );
                $topics[$key]['id'] = $item['id'];
                $topics[$key]['name'] = $item['name'];
                $topics[$key]['closing_date'] = $item['closing_date'];
                $topics[$key]['bypass'] = $item['bypass'];
                $topics[$key]['marked'] = $bookings[0]['marked'];
                $topics[$key]['submitted'] = $bookings[0]['submitted'];
            }
        }
        $heading = $this->objLanguage->languageText('mod_essayadmin_name','essayadmin');
        $this->setVarByRef('heading', $heading);

        $strAddNewTopic = $this->objLanguage->languageText('mod_essayadmin_addnewtopicarea','essayadmin');
        $objIcon = $this->objIcon;
        $skinIcons = $this->getObject('iconservice', 'ui');
        $linkAddNewTopic = '<a class="button" href="'.htmlspecialchars($this->uri(array('action'=>'addtopic')), ENT_QUOTES, 'UTF-8').'">'
            .$skinIcons->render('plus', array('decorative'=>true)).' '.htmlspecialchars($strAddNewTopic, ENT_QUOTES, 'UTF-8').'</a>';

        $objTable = $this->newObject('htmltable', 'htmlelements');
        $objTable->cssClass = 'chisimba-table';
        $objTable->cellpadding = 2;
        $objTable->cellspacing = 2;

        $tableHeader = array();
        $tableHeader[] = $this->objLanguage->languageText('mod_essayadmin_topicarea','essayadmin');
        $tableHeader[] = $this->objLanguage->languageText('mod_essayadmin_closedate','essayadmin');
        $tableHeader[] = $this->objLanguage->languageText('mod_essayadmin_submitted','essayadmin').' / '.$this->objLanguage->languageText('mod_essayadmin_marked','essayadmin');
        $tableHeader[] = $this->objLanguage->languageText('mod_essayadmin_editdelete','essayadmin');
        $objTable->addHeader($tableHeader, 'heading');
        $i=0;
        if (!empty($topics)) {
            foreach ($topics as $topic) {
                $class = ($i++%2) ? 'even':'odd';
                $strViewEssays = $this->objLanguage->languageText('mod_essayadmin_viewessays','essayadmin');

                $objLink = new link($this->uri(array('action'=>'view', 'id'=>$topic['id'])));
                $objLink->link = $topic['name'];
                $objLink->title = $strViewEssays;
                $linkView = $objLink->show();

                $editLabel=$this->objLanguage->languageText('word_edit');
                $iconEdit='<a class="chisimba-icon-button" aria-label="'.htmlspecialchars($editLabel,ENT_QUOTES,'UTF-8').'" href="'.htmlspecialchars($this->uri(array('action'=>'edittopic','id'=>$topic['id'])),ENT_QUOTES,'UTF-8').'">'.$skinIcons->render('pencil',array('decorative'=>true)).'</a>';

                $deleteLabel=$this->objLanguage->languageText('word_delete');
                $deleteText=$this->objLanguage->code2Txt('mod_essayadmin_deletetopic','essayadmin', array('TOPIC'=>$topic['name']));
                $iconDelete='<a class="chisimba-icon-button chisimba-button-danger" aria-label="'.htmlspecialchars($deleteLabel,ENT_QUOTES,'UTF-8').'" href="'.htmlspecialchars($this->uri(array('action'=>'deletetopic','id'=>$topic['id'])),ENT_QUOTES,'UTF-8').'" onclick="'.htmlspecialchars('return confirm('.json_encode($deleteText).');',ENT_QUOTES,'UTF-8').'">'.$skinIcons->render('trash-2',array('decorative'=>true)).'</a>';

                if ($topic['submitted'] == 0) {
                    $iconMark = '';
                } else {
                    $strMarkEssays = $this->objLanguage->languageText('mod_essayadmin_markessays','essayadmin');
                    $iconMark='<a class="chisimba-icon-button" aria-label="'.htmlspecialchars($strMarkEssays,ENT_QUOTES,'UTF-8').'" href="'.htmlspecialchars($this->uri(array('action'=>'marktopic','id'=>$topic['id'])),ENT_QUOTES,'UTF-8').'">'.$skinIcons->render('check-check',array('decorative'=>true)).'</a>';
                }

                $rowActions = '<div class="chisimba-row-actions">'
                    .$iconEdit
                    .$iconDelete
                    .$iconMark.'</div>';

//                $date = $this->objDateformat->formatDate($topic['closing_date']);
                if ($topic['bypass'] == '1') {
                    $date = '';
                } else {
                    $date = $this->objTimeAndDate->formatDateTime($topic['closing_date']);
                }

                $markedSubmitted = $topic['submitted'].' / '.$topic['marked'];

                $objTable->startRow();
                $objTable->addCell($linkView, '', '', '', $class);
                $objTable->addCell($date, '', '', '', $class);
                $objTable->addCell($markedSubmitted, '', '', '', $class);
                $objTable->addCell($rowActions, '', '', '', $class);
                $objTable->endRow();
            }
        } else {
            $objTable->startRow();
            $objTable->addCell($this->objLanguage->code2Txt('mod_essayadmin_notopicareasavailable', 'essayadmin'),'','','','noRecordsMessage','colspan="4"');
            $objTable->endRow();
        }

        $links = '';
        $links .= $linkAddNewTopic;

        return
            '<section class="chisimba-workspace"><div class="chisimba-actions">'.$links.'</div><div class="chisimba-table-wrap">'
            .$objTable->show().'</div></section>';
    }

    /**
    * Renders the essays.
    * @param string $topicAreaId The topic area ID
    * @return string Rendered content
    */
    function manageRenderEssays($topicAreaId) //$essays,$topic
    {
        // get topic name
        $topic=$this->dbtopic->getTopic($topicAreaId);
        // get essays in topic
        $essays=$this->dbessays->getEssays($topicAreaId);
        $head = $this->objLanguage->languageText('mod_essayadmin_topicarea','essayadmin').': '.$topic[0]['name'];
        $subhead=$this->objLanguage->languageText('mod_essayadmin_essays','essayadmin');
        $descriptionLabel=$this->objLanguage->languageText('mod_essayadmin_description','essayadmin');
        $instructionsLabel=$this->objLanguage->code2Txt('mod_essayadmin_instructions','essayadmin');
        $duedate=$this->objLanguage->languageText('mod_essayadmin_closedate','essayadmin');
        $view=$this->objLanguage->languageText('word_view');
        $title1=$this->objLanguage->languageText('word_edit');
        $title2=$this->objLanguage->languageText('word_delete');
        $title3=$this->objLanguage->languageText('mod_essayadmin_newessay','essayadmin');
        $topiclist=$this->objLanguage->languageText('mod_essayadmin_name','essayadmin').' '.$this->objLanguage->languageText('word_home');
        $viewSubmitted=$this->objLanguage->languageText('mod_essayadmin_viewbookedsubmitted','essayadmin');
        $noEssays = $this->objLanguage->code2Txt('mod_essayadmin_noessaysintopicarea','essayadmin');
        $skinIcons=$this->getObject('iconservice','ui');

        // edit topic icon
        $topicEdit='<a class="chisimba-icon-button" aria-label="'.htmlspecialchars($title1,ENT_QUOTES,'UTF-8').'" href="'.htmlspecialchars($this->uri(array('action'=>'edittopic','id'=>$topic[0]['id'])),ENT_QUOTES,'UTF-8').'">'.$skinIcons->render('pencil',array('decorative'=>true)).'</a>';

        // delete topic icon
        $topicDeleteText=$this->objLanguage->code2Txt('mod_essayadmin_deletetopic', 'essayadmin', array('TOPIC'=>$topic[0]['name']));
        $topicDelete='<a class="chisimba-icon-button chisimba-button-danger" aria-label="'.htmlspecialchars($title2,ENT_QUOTES,'UTF-8').'" href="'.htmlspecialchars($this->uri(array('action'=>'deletetopic','id'=>$topic[0]['id'])),ENT_QUOTES,'UTF-8').'" onclick="'.htmlspecialchars('return confirm('.json_encode($topicDeleteText).');',ENT_QUOTES,'UTF-8').'">'.$skinIcons->render('trash-2',array('decorative'=>true)).'</a>';

        $this->setVarByRef('heading',$head);

        $str = '<section class="chisimba-workspace"><div class="chisimba-actions">'.$topicEdit.$topicDelete.'</div>';
        // set confirm message if exists
        $confirm = $this->getParam('confirm');
        if($confirm == 'yes'){
            $msg = $this->getSession('confirm');
            $this->unsetSession('confirm');
            $objMsg = $this->newObject('timeoutmessage', 'htmlelements');
            $objMsg->setMessage($msg.'&nbsp;'.$this->objTimeAndDate->formatDateTime($this->objTimeAndDate->nowStorage()));
            $objMsg->setTimeOut(15000);
            $str .= '<p>'.$objMsg->show().'</p>';
        }

        $date = $this->objTimeAndDate->formatDateTime($topic[0]['closing_date']);
        $str .= '<dl class="chisimba-details">'
            .'<dt>'.htmlspecialchars($descriptionLabel,ENT_QUOTES,'UTF-8').'</dt><dd>'.$topic[0]['description'].'</dd>'
            .'<dt>'.htmlspecialchars($instructionsLabel,ENT_QUOTES,'UTF-8').'</dt><dd>'.$topic[0]['instructions'].'</dd>'
            .'<dt>'.htmlspecialchars($duedate,ENT_QUOTES,'UTF-8').'</dt><dd>'.htmlspecialchars($date,ENT_QUOTES,'UTF-8').'</dd>'
            .'</dl>';

        // Heading

        // add new essay icon
        $addicon='<a class="button chisimba-button-secondary chisimba-button-compact" href="'.htmlspecialchars($this->uri(array('action'=>'addessay','id'=>$topic[0]['id'])),ENT_QUOTES,'UTF-8').'">'.$skinIcons->render('plus',array('decorative'=>true)).' <span>'.htmlspecialchars($title3,ENT_QUOTES,'UTF-8').'</span></a>';
        $str .= '<div class="chisimba-section-heading"><h2>'.htmlspecialchars($subhead,ENT_QUOTES,'UTF-8').'</h2>'.$addicon.'</div>';

        // Display essay list in table
        $objTable = new htmltable();
        $objTable->cssClass='chisimba-table';
        //$objTable->width='99%';
        $objTable->cellpadding=2;
        $objTable->cellspacing=2;

        $tableHeader = array();
        $tableHeader[] = '#';
        $tableHeader[] = $this->objLanguage->languageText('mod_essayadmin_essay','essayadmin');
        $tableHeader[] = $this->objLanguage->languageText('mod_essayadmin_notes','essayadmin');
        $tableHeader[] = '&nbsp;';
        $objTable->addHeader($tableHeader, 'heading');

        if(!empty($essays)){
            $i=0;
            foreach($essays as $essay){
                $class = ($i++%2)? 'even':'odd';

                // edit essay
                $view = htmlspecialchars($essay['topic'],ENT_QUOTES,'UTF-8');

                $edit='<a class="chisimba-icon-button" aria-label="'.htmlspecialchars($title1,ENT_QUOTES,'UTF-8').'" href="'.htmlspecialchars($this->uri(array('action'=>'editessay','essay'=>$essay['id'],'id'=>$topic[0]['id'])),ENT_QUOTES,'UTF-8').'">'.$skinIcons->render('pencil',array('decorative'=>true)).'</a>';
                // delete essay display confirmation
                $deleteText=$this->objLanguage->code2Txt('mod_essayadmin_deleteessay', 'essayadmin', array('ESSAY'=>$essay['topic']));
                $delete='<a class="chisimba-icon-button chisimba-button-danger" aria-label="'.htmlspecialchars($title2,ENT_QUOTES,'UTF-8').'" href="'.htmlspecialchars($this->uri(array('action'=>'deleteessay','essay'=>$essay['id'],'id'=>$topic[0]['id'])),ENT_QUOTES,'UTF-8').'" onclick="'.htmlspecialchars('return confirm('.json_encode($deleteText).');',ENT_QUOTES,'UTF-8').'">'.$skinIcons->render('trash-2',array('decorative'=>true)).'</a>';
                $icons='<div class="chisimba-row-actions">'.$edit.$delete.'</div>';

                if(strlen($essay['notes']) > 100){
                    $pos = strpos($essay['notes'], ' ', 100);
                    $notes = substr($essay['notes'], 0, $pos).'...';
                }else{
                    $notes = $essay['notes'];
                }

                //$objTable->row_attributes=' height="25"';
                $objTable->startRow();
                $objTable->addCell($i, '','','',$class);
                $objTable->addCell($view,'','','',$class);
                $objTable->addCell($notes,'','','',$class);
                $objTable->addCell($icons,'','','',$class);
                $objTable->endRow();
            }
        }else{
            //$objTable->row_attributes=' height="15"';
            $objTable->startRow();
            $objTable->addCell($noEssays,'','','','noRecordsMessage','colspan="4"');
            $objTable->endRow();
        }

        $str .= '<div class="chisimba-table-wrap">'.$objTable->show().'</div>';

        $strHome = $this->objLanguage->languageText('mod_essayadmin_home','essayadmin');
        $str .= '<div class="chisimba-form-actions">'
            .'<a class="button" href="'.htmlspecialchars($this->uri(array('action'=>'marktopic','id'=>$topic[0]['id'])),ENT_QUOTES,'UTF-8').'">'.$skinIcons->render('eye',array('decorative'=>true)).' <span>'.htmlspecialchars($viewSubmitted,ENT_QUOTES,'UTF-8').'</span></a>'
            .'<a class="button chisimba-button-secondary" href="'.htmlspecialchars($this->uri(array()),ENT_QUOTES,'UTF-8').'">'.$skinIcons->render('arrow-left',array('decorative'=>true)).' <span>'.htmlspecialchars($strHome,ENT_QUOTES,'UTF-8').'</span></a>'
            .'</div></section>';
        return $str;
    }
}
?>
