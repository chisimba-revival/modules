<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of block_discussionmoderation_class_inc
 *
 * @author monwabisi
 */
// security check - must be included in all scripts
if (!$GLOBALS['kewl_entry_point_run']) {
        die("You cannot view this page directly");
}

// end security check
class block_topicmoderation extends ChisimbaObject {

    var $objTopic;
    var $objUser;
    var $userID;
    var $objLanguage;
    var $objDiscussion;
    var $contextCode;
    var $contextObject;

    //put your code here
    function init() {
        $this->loadClass('form', 'htmlelements');
        $this->loadClass('link', 'htmlelements');
        $this->loadClass('radio', 'htmlelements');
        $this->loadClass('button', 'htmlelements');
        $this->loadClass('hiddeninput', 'htmlelements');
        $this->loadClass('dropdown', 'htmlelements');
        $this->loadClass('htmlheading', 'htmlelements');
        $this->objTopic = $this->getObject('dbtopic', 'discussion');
        $this->objUser = $this->getObject('user', 'security');
        $this->userId = $this->objUser->userId();
        $this->objLanguage = $this->getObject('language', 'language');
        $this->objDiscussion = $this->getObject('dbdiscussion', 'discussion');
        $this->csrf = $this->getObject('nativeauthwebcomposition', 'security')->build()['csrf'];
        $this->title = "Moderate topic";
        // Get Context Code Settings
        $this->contextObject = & $this->getObject('dbcontext', 'context');
        $id = $this->getParam('id');
        $topic = $this->objTopic->getTopicDetails($id);
        $this->title =  $this->objLanguage->languageText('mod_discussion_moderatetopic', 'discussion', 'Moderate Topic') . ': ' . $topic['post_title'];
        
        $this->contextCode = $this->contextObject->getContextCode();
        $style = '<style type="text/css">
.switchmenutext { text-align: left;}
</style>';
    }

    function buildLegacyForm() {
        $html = "";
        $id = $this->getParam('id');
        $topic = $this->objTopic->getTopicDetails($id);
        $hiddeninput = new hiddeninput('id', $topic['topic_id']);
        $objHighlightLabels = $this->getObject('highlightlabels', 'htmlelements');

        // Get a list of all the topics in discussion, excluding thisone
        $additionalSql = ' AND tbl_discussion_topic.id != "' . $topic['topic_id'] . '" ';
        $otherTopicsInDiscussion = $this->objTopic->showTopicsInDiscussion($topic['discussion_id'], $this->userId, NULL, NULL, NULL, $additionalSql);

        $optionsCount = 1;


        if ($this->getParam('message') == 'deletecancelled') {
            echo '<p align="center"><span class="confirm">' . stripslashes($this->objLanguage->languageText('mod_discussion_topicwasnotdeleted', 'discussion', 'This Topic was not Deleted - The Delete confirmation was set to "No".')) . '</span></p>';
        }

        $html =  '<p>' . $this->objLanguage->languageText('mod_discussion_whattodowithtopic', 'discussion', 'What would you like to do with this topic?') . '</p>';

        $switchmenu = $this->newObject('switchmenu', 'htmlelements');
        $switchmenu->mainId = 'moderate';

        /*         * ************************************************************************************
          ////////////////////////// FIRST OPTION - DELETING A TOPIC  ///////////////////////////
         * ************************************************************************************ */

        $deleteForm = new form('moderate_deletetopic', $this->uri(array('action' => 'moderate_deletetopic')));

        if ($topic['topic_tangent_parent'] == '0') {
            $tangents = $this->objTopic->getTangents($id);
            $topicParent = '';
        } else {
            $tangents = array(); // Create Array of No Values
            $topicParent = $this->objTopic->getTopicDetails($topic['topic_tangent_parent']);
        }
        if ($topicParent != '') {
            $moderateParent = new link($this->uri(array('action' => 'moderatetopic', 'id' => $topicParent['topic_id'])));
            $moderateParent->link = 'click here';

            $str = $this->objLanguage->languageText('mod_discussion_warntopicistangent', 'discussion', 'This topic is a tangent to <strong>[-POSTTITLE-]</strong>. This option will only affect this tangent. <br />If you would like to moderate the parent topic, please [-CLICKHERE-]');

            $str = str_replace('[-CLICKHERE-]', $moderateParent->show(), $str);

            $str = str_replace('[-POSTTITLE-]', $topicParent['post_title'], $str);

            $deleteForm->addToForm($str);

            //$deleteForm->addToForm('<p>This topic is a tangent to <strong>'.$topicParent['post_title'].'</strong>. This option will only affect this tangent. <br />If you would like to moderate the parent topic, please '.$moderateParent->show().'.</p>');
        }

        if ((is_countable($tangents) ? count($tangents) : 0) > 0) {
            $str = $this->objLanguage->languageText('mod_discussion_topichasfollwoingtangents', 'discussion', 'This topic has the following [-COUNTTANGENTS-] tangent(s). Please indicate what you would like to happen to them as well?');

            $str = str_replace('[-COUNTTANGENTS-]', (is_countable($tangents) ? count($tangents) : 0), $str);

            $deleteForm->addToForm($str);

            $tangentList = '<ul>';
            foreach ($tangents as $tangent) {
                $tangentList .= '<li>' . $tangent['post_title'] . '</li>';
            }
            $tangentList .= '</ul>';

            $deleteForm->addToForm($tangentList);
        }

        $deleteForm->addToForm('<fieldset><legend>' . $this->objLanguage->languageText('mod_discussion_deleteoptions', 'discussion', 'Delete Options') . '</legend><p>');

        if ((is_countable($tangents) ? count($tangents) : 0) > 0) {
            $deleteForm->addToForm('a) ');
        }

        $deleteForm->addToForm($this->objLanguage->languageText('mod_discussion_confirmdeletetopic', 'discussion', 'Are you sure you want to delete this topic?').'<br/>');

        $deleteConfirm = new radio('delete');
        $deleteConfirm->addOption('0', $this->objLanguage->languageText('word_no', 'system', 'No'));
        $deleteConfirm->addOption('1', ''. $this->objLanguage->languageText('word_yes', 'system', 'Yes') . '');
        $deleteConfirm->setBreakSpace(' &nbsp; ');
        $deleteConfirm->setSelected('0');

        $deleteForm->addToForm($deleteConfirm->show() . '</p>');

        if ((is_countable($tangents) ? count($tangents) : 0) > 0) {
            $deleteForm->addToForm('<p>b) ' . $this->objLanguage->languageText('mod_discussion_whathappentotangents', 'discussion', 'What should happen to the tangents?') . '</p>');

            $tangentOption = new radio('tangentoption');
            $tangentOption->addOption('delete', $this->objLanguage->languageText('mod_discussion_deletealltangents', 'discussion', 'Delete all Tangents related to this topic'));

            $dropdown = new dropdown('topicmove');

            if ((is_countable($otherTopicsInDiscussion) ? count($otherTopicsInDiscussion) : 0) > 0) {
                foreach ($otherTopicsInDiscussion as $discussiontopic) {
                    $dropdown->addOption($discussiontopic['topic_id'], $discussiontopic['post_title']);
                }
                $tangentOption->addOption('move', $this->objLanguage->languageText('mod_discussion_movetangentstofollowingtopic', 'discussion', 'Move them to the following topic') . ' - ' . $dropdown->show());
            }

            $tangentOption->addOption('newtopic', $this->objLanguage->languageText('mod_discussion_movetangentstonewtopic', 'discussion', 'Move the Tangents to Topics. Each Tangent will be a new topic.'));

            // Preserve Users Default Selected Option
            $defaultOption = strtolower($this->getParam('option', NULL));

            if ($defaultOption == 'delete' || $defaultOption == 'move' || $defaultOption == 'newtopic') {
                $tangentOption->setSelected($defaultOption);
            } else {
                $tangentOption->setSelected('delete');
            }
            // End: Preserve

            $tangentOption->setBreakSpace('<br />');

            $deleteForm->addToForm('<blockquote>' . $tangentOption->show() . '</blockquote>');
        }

        $button = new button('confirmdelete');
        $button->cssClass = 'delete';
        $button->value = $this->objLanguage->languageText('mod_discussion_confirmdelete', 'discussion', 'Confirm Delete');
        $button->setToSubmit();



        $deleteForm->addToForm('<p align="center">' . $button->show() . '</p></fieldset>');


//Add Hidden Id - Common to both
        $deleteForm->addToForm($hiddeninput->show());

        $switchmenu->addBlock($optionsCount . ') ' . $this->objLanguage->languageText('mod_discussion_deletethetopic', 'discussion', 'Delete the Topic'), $deleteForm->show(), 'switchmenutext');


        /*         * ************************************************************************************
          //////////////////// SECOND OPTION - MOVING TO ANOTHER FORUM  ////////////////////
         * ************************************************************************************ */


// Only show this option if there are other topics.
// You cant move a topic as a tangent to another topic if there aren't any other topics


        $otherDiscussions = $this->objDiscussion->otherDiscussions($topic['discussion_id'], $this->contextCode);
        if ((is_countable($otherDiscussions) ? count($otherDiscussions) : 0) > 0) {

            // Increase Options Count for next item
            $optionsCount++;

            $moveToDiscussionForm = new form('movetopictodiscussion', $this->uri(array('action' => 'moderate_movetodiscussion')));

            $dropdown = new dropdown('discussionmove');

            foreach ($otherDiscussions as $discussion) {
                $dropdown->addOption($discussion['discussion_id'], $discussion['discussion_name']);
            }
            $moveToDiscussionForm->addToForm($this->objLanguage->languageText('mod_discussion_movetopictofollowingdiscussion', 'discussion', 'Move the Topic to the following discussion') . ': ' . $dropdown->show());

            /// CONTINUE ADD BUTTON TO FORM

            $button = new button('confirmmovetotangent');
            $button->value = $this->objLanguage->languageText('mod_discussion_confirmmovetopic', 'discussion', 'Confirm Move Topic');
            $button->setToSubmit();

            $moveToDiscussionForm->addToForm('<p>' . $button->show() . '</p>');

            //Add Hidden Id - Common to both
            $moveToDiscussionForm->addToForm($hiddeninput->show());


            $switchmenu->addBlock($optionsCount . ') ' . $this->objLanguage->languageText('mod_discussion_movetopictoanotherdiscussion', 'discussion', 'Move the Topic to another Discussion'), $moveToDiscussionForm->show(), 'switchmenutext');
        }



        /*         * ************************************************************************************
          //////////////////// THIRD OPTION - MOVING TO TOPIC TO A TANGENT  ////////////////////
         * ************************************************************************************ */


// Only show this option if there are other topics.
// You cant move a topic as a tangent to another topic if there aren't any other topics


        if ((is_countable($otherTopicsInDiscussion) ? count($otherTopicsInDiscussion) : 0) > 0) {

            // Increase Options Count for next item
            $optionsCount++;

            $moveToTangentForm = new form('moderate_movetotangent', $this->uri(array('action' => 'moderate_movetotangent')));
            $dropdown = new dropdown('topicmove');

            foreach ($otherTopicsInDiscussion as $discussiontopic) {
                if ($discussiontopic['topic_id'] != $topic['topic_tangent_parent']) {
                    $dropdown->addOption($discussiontopic['topic_id'], $discussiontopic['post_title']);
                }
            }
            $moveToTangentForm->addToForm($this->objLanguage->languageText('mod_discussion_movetopicastangent', 'discussion', 'Move the Topic as a tangent to the following topic') . ': ' . $dropdown->show());

            if ((is_countable($tangents) ? count($tangents) : 0) > 0) {
                $str = $this->objLanguage->languageText('mod_discussion_tangentsmovedwithtopic', 'discussion', '<strong>Note</strong> This topic has [-COUNTTANGENTS-] tangent(s). They will automatically become tangents to the selected topic.');
                $str = str_replace('[-COUNTTANGENTS-]', (is_countable($tangents) ? count($tangents) : 0), $str);
                $moveToTangentForm->addToForm('<p>' . $str . '</p>');
            }

            $button = new button('confirmmovetotangent');
            $button->value = $this->objLanguage->languageText('mod_discussion_confirmmovetopic', 'discussion', 'Confirm Move Topic');
            $button->setToSubmit();

            /// CONTINUE ADD BUTTON TO FORM

            $moveToTangentForm->addToForm('<p>' . $button->show() . '</p>');

            //Add Hidden Id - Common to both
            $moveToTangentForm->addToForm($hiddeninput->show());

            $switchmenu->addBlock($optionsCount . ') ' . $this->objLanguage->languageText('mod_discussion_movetopicastangent', 'discussion', 'Move it as a Tangent to another Topic'), $moveToTangentForm->show(), 'switchmenutext');
        }




        /*         * ************************************************************************************
          //////////////////// FOURTH OPTION - MOVING TANGENT TO A NEW TOPIC  ////////////////////
         * ************************************************************************************ */

        if ($topic['topic_tangent_parent'] != '0') {
            // Increase Options Count for next item
            $optionsCount++;

            $moveToNewTopicForm = new form('moderate_movetonewtopic', $this->uri(array('action' => 'moderate_movetonewtopic')));

            $moveToNewTopicForm->addToForm($this->objLanguage->languageText('mod_discussion_confirmovetopicastangent', 'discussion', 'Are you sure you want to move this tangent to a new topic?') . ' ');

            $moveConfirm = new radio('move');
            $moveConfirm->addOption('0', '<strong><span class="error">' . $this->objLanguage->languageText('word_no', 'No') . '</span></strong>');
            $moveConfirm->addOption('1', '<strong><span class="error">' . $this->objLanguage->languageText('word_yes', 'Yes') . '</span></strong>');
            $moveConfirm->setBreakSpace(' &nbsp; ');
            $moveConfirm->setSelected('0');

            $moveToNewTopicForm->addToForm($deleteConfirm->show() . '</p>');

            $button = new button('confirmdelete');
            $button->cssClass = 'delete';
            $button->value = $this->objLanguage->languageText('mod_discussion_confirmmovetonewtopic', 'discussion', 'Confirm Move to New Topic');
            $button->setToSubmit();

            $moveToNewTopicForm->addToForm('<p>' . $button->show() . '</p>');

            //Add Hidden Id - Common to both
            $moveToNewTopicForm->addToForm($hiddeninput->show());

            $switchmenu->addBlock($optionsCount . ') ' . $this->objLanguage->languageText('mod_discussion_confirmmovingnewtopic', 'discussion', 'Move it as a New Topic'), $moveToNewTopicForm->show(), 'switchmenutext');
        }

        /*         * ************************************************************************************
          //////////////////// FIFTH OPTION - LOCKING / UNLOCKING TOPIC  ///////////////////////
         * ************************************************************************************ */


// Increase Options Count for next item
        $optionsCount++;





        $topicStatusForm = new form('topicStatusForm', $this->uri(array('module' => 'discussion', 'action' => 'changetopicstatus')));

        $objElement = new radio('topic_status');
        $objElement->addOption('OPEN', '<strong>' . $this->objLanguage->languageText('word_open') . '</strong> - ' . $this->objLanguage->languageText('mod_discussion_allowusersreply', 'discussion'));
        $objElement->addOption('CLOSE', '<strong>' . $this->objLanguage->languageText('word_close') . '</strong> - ' . $this->objLanguage->languageText('mod_discussion_preventusersreply', 'discussion'));

        if ($topic['topicstatus'] == 'OPEN') {
            $objElement->setSelected('OPEN');
            $displayStyle = 'none';
        } else {
            $objElement->setSelected('CLOSE');
            $displayStyle = 'block';
        }
        $objElement->extra = ' onClick="showReasonForm()"';
        $objElement->setBreakSpace('<br />');
        $topicStatusForm->addToForm('<p>' . $objElement->show() . '</p>');

        $topicStatusForm->addToForm('<div id="closeReason" style="display:' . $displayStyle . '">');

        $header = new htmlheading();
        $header->type = 3;
        $header->str = $this->objLanguage->languageText('mod_discussion_providereason', 'discussion');
        $topicStatusForm->addToForm($header->show());

        $editor = &$this->newObject('htmlarea', 'htmlelements');
        $reasonContent = $topic['lockreason'];

        if ($reasonContent == '') {
            $reasonContent = ' <p>Here are some typical reasons why  topics are closed - Please edit this message</p>
 <ul>
   <li>The topic has reached a conclusion, the issue has been fully addressed </li>
   <li>The topic is moving into a direction out of bounds </li>
   <li>The topic is being used for inappropriate speech.</li>
 </ul>';
        }

        $editor->setName('reason');
        $editor->setRows(10);
        $editor->setColumns('100%');
        $editor->setContent($reasonContent);

        $topicStatusForm->addToForm($editor);

        $topicStatusForm->addToForm('</div>');

        $submitButton = new button('submitform', $this->objLanguage->languageText('mod_discussion_updatetopicstatus', 'discussion', 'Update Topic Status'));
        $submitButton->cssClass = 'save';
        $submitButton->setToSubmit();

        $topicStatusForm->addToForm('<p>' . $submitButton->show() . '</p>');

        $topicHiddenInput = new textinput('topic');
        $topicHiddenInput->fldType = 'hidden';
        $topicHiddenInput->value = $topic['topic_id'];
        $topicStatusForm->addToForm($topicHiddenInput->show());


        $switchmenu->addBlock($optionsCount . ') ' . $this->objLanguage->languageText('mod_discussion_lockingunlockingtopic', 'discussion', 'Locking / Unlocking a Topic'), $topicStatusForm->show(), 'switchmenutext');

        /*         * ************************************************************************************
          //////////////////// SIXTH OPTION - MAKING THE TOPIC STICKY  //////////////////////////
         * ************************************************************************************ */


// Increase Options Count for next item
        $optionsCount++;

        if ($topic['topic_tangent_parent'] == '0') {

            $stickyTopicForm = new form('stickyTopicForm', $this->uri(array('module' => 'discussion', 'action' => 'moderate_topicsticky')));

            $objElement = new radio('stickytopic');
            $objElement->addOption('1', '<strong>' . $this->objLanguage->languageText('word_sticky', 'discussion', 'Sticky') . '</strong> - ' . $this->objLanguage->languageText('mod_discussion_stickytopicexplained', 'discussion', 'A Sticky topic always appears (sticks) on the top of a discussion.'));
            $objElement->addOption('0', '<strong>' . $this->objLanguage->languageText('word_notsticky', 'discussion', 'Not Sticky') . '</strong> - ' . $this->objLanguage->languageText('mod_discussion_notstickytopicexplained', 'discussion', 'The topic will appear according to standard sorting criteria.'));

            $objElement->setSelected($topic['sticky']);


            $objElement->setBreakSpace('<br />');
            $stickyTopicForm->addToForm('<p>' . $objElement->show() . '</p>');

            $submitButton = new button('submitform2', $this->objLanguage->languageText('mod_discussion_updatestickystatus', 'discussion', 'Update Sticky Status'));
            $submitButton->cssClass = 'save';
            $submitButton->setToSubmit();

            $stickyTopicForm->addToForm('<p>' . $submitButton->show() . '</p>');

            $stickyTopicForm->addToForm($hiddeninput->show());

            $switchmenu->addBlock($optionsCount . ') ' . $this->objLanguage->languageText('mod_discussion_makingtopicsticky', 'discussion', 'Making a Topic Sticky or Not'), $stickyTopicForm->show(), 'switchmenutext');
        }

// Further Options
//    - Placing a comment on the topic of the topic
//    - Making the Topic Invisible


        $html .=  $switchmenu->show();

        $discussion = $this->objDiscussion->getDiscussion($topic['discussion_id']);
        $returnLink = new link($this->uri(array('action' => 'viewtopic', 'id' => $topic['topic_id'])));
        $returnLink->link = $this->objLanguage->languageText('mod_discussion_returntotopic', 'discussion', 'Return to Topic') . ' - ' . $topic['post_title'];

        $returnDiscussionLink = new link($this->uri(array('action' => 'discussion', 'id' => $topic['discussion_id'])));
        $returnDiscussionLink->link = $this->objLanguage->languageText('mod_discussion_returntodiscussion', 'discussion', 'Return to Discussion') . ' - ' . $discussion['discussion_name'];

        $html .= '<p align="center">' . $returnLink->show() . ' / ' . $returnDiscussionLink->show() . '</p>';
        return $objHighlightLabels->show().$html;
    }

    /** Render the moderation actions as a semantic, course-scoped workspace. */
    function buildForm() {
        $e=static fn($value)=>htmlspecialchars((string)$value,ENT_QUOTES,'UTF-8');
        $topic=$this->objTopic->getTopicDetails(trim((string)$this->getParam('id')));
        if(!is_array($topic)){return '<main class="chisimba-workspace"><section class="chisimba-card discussion-empty-state"><h1>Topic unavailable</h1></section></main>';}
        $discussion=$this->objDiscussion->getDiscussion($topic['discussion_id']);
        if(!is_array($discussion)){return '<main class="chisimba-workspace"><section class="chisimba-card discussion-empty-state"><h1>Discussion unavailable</h1></section></main>';}
        $topicId=(string)$topic['topic_id'];$discussionId=(string)$topic['discussion_id'];$type=trim((string)$this->getParam('type','context'))?:'context';
        $topicUrl=$this->uri(array('action'=>'viewtopic','id'=>$topicId,'type'=>$type));$discussionUrl=$this->uri(array('action'=>'discussion','id'=>$discussionId,'type'=>$type));
        $token=function($action)use($topicId,$e){return '<input type="hidden" name="csrf_token" value="'.$e($this->csrf->issue('disc_mod_'.hash('sha256',$action.'|'.$topicId))).'">';};
        $hidden='<input type="hidden" name="id" value="'.$e($topicId).'">';$icons=$this->getObject('iconservice','ui');
        $otherDiscussions=(array)$this->objDiscussion->otherDiscussions($discussionId,$this->contextCode);
        $otherTopics=(array)$this->objTopic->showTopicsInDiscussion($discussionId,$this->userId,null,null,null,' AND tbl_discussion_topic.id != "'.addslashes($topicId).'" ');
        ob_start(); ?>
<main class="chisimba-workspace chisimba-flow discussion-workspace discussion-moderation<?php echo ($discussion['assessment_enabled']??'N')==='Y'?' discussion-moderation--assessed':''; ?>">
<header class="chisimba-page-header chisimba-card"><div><p class="chisimba-eyebrow">Topic moderation</p><h1><a href="<?php echo $e($topicUrl); ?>"><?php echo $e($topic['post_title']); ?></a></h1><p>In <a href="<?php echo $e($discussionUrl); ?>"><?php echo $e($discussion['discussion_name']); ?></a></p></div></header>
<p>Choose the action you need. Nothing changes until its clearly labelled button is selected.</p>
<?php if(($discussion['assessment_enabled']??'N')==='Y'): ?><p class="chisimba-notice">This is an assessed discussion. Topics and established contributions cannot be moved or deleted.</p><?php endif; ?>
<section class="discussion-moderation-grid">
<details class="chisimba-card discussion-moderation-action"><summary><?php echo $icons->render('lock',array('decorative'=>true)); ?><span>Open or close replies</span></summary><form method="post" action="<?php echo $e($this->uri(array('action'=>'changetopicstatus'))); ?>"><?php echo $token('changetopicstatus'); ?><input type="hidden" name="topic" value="<?php echo $e($topicId); ?>"><fieldset><legend>Reply status</legend><label><input type="radio" name="topic_status" value="OPEN"<?php echo ($topic['topicstatus']??'')==='OPEN'?' checked':''; ?>> Open — learners may reply</label><label><input type="radio" name="topic_status" value="CLOSE"<?php echo ($topic['topicstatus']??'')==='CLOSE'?' checked':''; ?>> Closed — new replies are prevented</label></fieldset><label>Reason for closing<textarea name="reason" rows="5" maxlength="4000"><?php echo $e(strip_tags((string)($topic['lockreason']??''))); ?></textarea></label><button class="button" type="submit">Save reply status</button></form></details>
<?php if(($topic['topic_tangent_parent']??'0')==='0'): ?><details class="chisimba-card discussion-moderation-action"><summary><?php echo $icons->render('pin',array('decorative'=>true)); ?><span>Pin or unpin topic</span></summary><form method="post" action="<?php echo $e($this->uri(array('action'=>'moderate_topicsticky'))); ?>"><?php echo $token('moderate_topicsticky').$hidden; ?><fieldset><legend>Topic position</legend><label><input type="radio" name="stickytopic" value="1"<?php echo (string)($topic['sticky']??'0')==='1'?' checked':''; ?>> Pinned at the top</label><label><input type="radio" name="stickytopic" value="0"<?php echo (string)($topic['sticky']??'0')!=='1'?' checked':''; ?>> Standard ordering</label></fieldset><button class="button" type="submit">Save topic position</button></form></details><?php endif; ?>
<?php if($otherDiscussions): ?><details class="chisimba-card discussion-moderation-action"><summary><?php echo $icons->render('folder-input',array('decorative'=>true)); ?><span>Move to another discussion</span></summary><form method="post" action="<?php echo $e($this->uri(array('action'=>'moderate_movetodiscussion'))); ?>"><?php echo $token('moderate_movetodiscussion').$hidden; ?><label>Destination discussion<select name="discussionmove" required><?php foreach($otherDiscussions as $item): ?><option value="<?php echo $e($item['discussion_id']); ?>"><?php echo $e($item['discussion_name']); ?></option><?php endforeach; ?></select></label><button class="button" type="submit">Move topic</button></form></details><?php endif; ?>
<?php if($otherTopics): ?><details class="chisimba-card discussion-moderation-action"><summary><?php echo $icons->render('corner-down-right',array('decorative'=>true)); ?><span>Make this a tangent</span></summary><form method="post" action="<?php echo $e($this->uri(array('action'=>'moderate_movetotangent'))); ?>"><?php echo $token('moderate_movetotangent').$hidden; ?><label>Parent topic<select name="topicmove" required><?php foreach($otherTopics as $item): ?><option value="<?php echo $e($item['topic_id']); ?>"><?php echo $e($item['post_title']); ?></option><?php endforeach; ?></select></label><button class="button" type="submit">Move as tangent</button></form></details><?php endif; ?>
<?php if(($topic['topic_tangent_parent']??'0')!=='0'): ?><details class="chisimba-card discussion-moderation-action"><summary><?php echo $icons->render('corner-up-left',array('decorative'=>true)); ?><span>Make this a main topic</span></summary><form method="post" action="<?php echo $e($this->uri(array('action'=>'moderate_movetonewtopic'))); ?>"><?php echo $token('moderate_movetonewtopic').$hidden; ?><input type="hidden" name="delete" value="1"><p>This tangent will become an independent topic in the same discussion.</p><button class="button" type="submit">Make main topic</button></form></details><?php endif; ?>
<details class="chisimba-card discussion-moderation-action discussion-moderation-danger"><summary><?php echo $icons->render('trash-2',array('decorative'=>true)); ?><span>Delete topic</span></summary><form method="post" action="<?php echo $e($this->uri(array('action'=>'moderate_deletetopic'))); ?>"><?php echo $token('moderate_deletetopic').$hidden; ?><input type="hidden" name="delete" value="1"><?php if((is_countable($this->objTopic->getTangents($topicId))?count($this->objTopic->getTangents($topicId)):0)>0): ?><fieldset><legend>Existing tangents</legend><label><input type="radio" name="tangentoption" value="newtopic" checked> Keep tangents as main topics</label><label><input type="radio" name="tangentoption" value="delete"> Delete tangents too</label><?php if($otherTopics): ?><label><input type="radio" name="tangentoption" value="move"> Move tangents to <select name="topicmove"><?php foreach($otherTopics as $item): ?><option value="<?php echo $e($item['topic_id']); ?>"><?php echo $e($item['post_title']); ?></option><?php endforeach; ?></select></label><?php endif; ?></fieldset><?php endif; ?><p>This permanently removes the topic and its posts.</p><button class="button chisimba-button-danger" type="submit">Delete topic permanently</button></form></details>
</section></main>
<?php $html=ob_get_contents();ob_end_clean();return $html;
    }

    function show() {
        return $this->buildForm();
    }

}

?>
