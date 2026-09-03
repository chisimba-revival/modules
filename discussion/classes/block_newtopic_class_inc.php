<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of newPHPClass
 *
 * @author monwabisi
 */

// security check - must be included in all scripts
if (!$GLOBALS['kewl_entry_point_run']) {
        die("You cannot view this page directly");
}

// end security check
class block_newtopic extends ChisimbaObject {

    var $objDiscussion;
    var $objLanguage;
    var $objUser;
    var $contextObject;
    var $contextCode;
    var $discussionTypes;
    var $objDiscussionSubscriptions;
    var $objTopicSubscriptions;

    //put your code here
    public function init() {
        $this->loadClass('form', 'htmlelements');
        $this->loadClass('textinput', 'htmlelements');
        $this->loadClass('textarea', 'htmlelements');
        $this->loadClass('button', 'htmlelements');
        $this->loadClass('dropdown', 'htmlelements');
        $this->loadClass('label', 'htmlelements');
        $this->loadClass('iframe', 'htmlelements');
        $this->loadClass('htmlheading', 'htmlelements');
        $this->loadClass('link', 'htmlelements');
        $this->loadClass('radio', 'htmlelements');
        $this->loadClass('hiddeninput', 'htmlelements');
        $this->objDiscussion = $this->getObject('dbdiscussion', 'discussion');
        $this->objLanguage = $this->getObject('language', 'language');
        $this->objUser = $this->getObject('user', 'security');
        // Get Context Code Settings
        $this->contextObject = & $this->getObject('dbcontext', 'context');
        $this->objDiscussionType = & $this->getObject('dbdiscussiontypes');
        $this->contextCode = $this->contextObject->getContextCode();
        $this->objTopicSubscriptions = & $this->getObject('dbtopicsubscriptions');
        // Load Discussion Subscription classes
        $this->objDiscussionSubscriptions = & $this->getObject('dbdiscussionsubscriptions');
        $this->title = '';
    }

    public function biuldEntryForm() {
        $discussionId =  $this->getParam('id');
        $discussion = $this->objDiscussion->getDiscussion($discussionId);
        $objHighlightLabels = $this->getObject('highlightlabels', 'htmlelements');
        $js = '<script type="text/javascript">
      function SubmitForm()
    {
    if (document.getElementById("title").value == ""){
    alert("Provide title");
    }else
    {
        document.forms["newTopicForm"].submit();
    }
    }


</script>';
        $mode = '';
        $temporaryId = '';
        $details ='';
                // Check if form is a result of server-side validation or not'
        if (in_array($this->getParam('message'), array('missing', 'savefailed'), true)) {
            $details = $this->getSession($this->getParam('tempid'));
//            $this->setVarByRef('details', $details);
            $temporaryId = $details['temporaryId'];
            $this->setVar('mode', 'fix');
            $mode = 'fix';
        } else {
            $temporaryId = $this->objUser->userId() . '_' . time();
            $this->setVar('mode', 'new');
            $mode = "new";
        }
        //topic form
        $discussiontype = 'root';
        $newTopicForm = new form('newTopicForm', $this->uri(array('module' => 'discussion', 'action' => 'savenewtopic', 'type' => $discussiontype)));
        $newTopicForm->displayType = 3;
        $newTopicForm->addRule('title', $this->objLanguage->languageText('mod_discussion_addtitle', 'discussion'), 'required');
        //topic table
        $discussionLink = new link($this->uri(array('action' => 'discussion', 'id' => $discussionId)));
        $discussionLink->link = $discussion['discussion_name'];
        $discussionLink->title = $this->objLanguage->languageText('mod_discussion_returntodiscussion', 'discussion');
        //heading
        $header = $this->getObject('htmlheading', 'htmlelements');
        $header->type = 1;
        $header->str = $discussionLink->show() . ' - ' . $this->objLanguage->languageText('mod_discussion_postnewmessage', 'discussion');
        $mode = $this->getVar('mode');
        if ($mode == 'fix') {
            $errorText = $this->getParam('message') === 'savefailed'
                ? $this->objLanguage->languageText('mod_discussion_savefailed', 'discussion', 'Your topic could not be sent. Your work has been preserved; please try again.')
                : $this->objLanguage->languageText('mod_discussion_messageisblank', 'discussion');
            echo '<div class="chisimba-notice chisimba-notice--error" role="alert"><strong>' . htmlspecialchars($errorText, ENT_QUOTES, 'UTF-8') . '</strong></div>';
        }
        //table
        $addTable = $this->getObject('htmltable', 'htmlelements');
        $addTable->width = '99%';
        $addTable->cellpadding = 10;
        $addTable->cssClass = 'discussion-compose';
        //title
        $titleInput = new textinput('title');
        $titleInput->size = 50;
        $titleInput->setId('title');
        // Title
        $addTable->startRow();
        $subjectLabel = new label($this->objLanguage->languageText('word_subject', 'system') . ':', 'input_title');
        $addTable->addCell($subjectLabel->show(), 120);
        if ($mode == 'fix') {
            $titleInput->value = $details['title'];
        }

        $addTable->addCell($titleInput->show());
        $addTable->endRow();

// Type of Topic

        $addTable->startRow();

        $discussionTypeLabel = new label('<nobr>' . $this->objLanguage->languageText('mod_discussion_typeoftopic', 'discussion') . ':</nobr>', 'input_discussionType');
        $addTable->addCell($discussionTypeLabel->show(), 120);
        $discussionTypes = $this->objDiscussionType->getDiscussionTypes();
        $discussionType = new dropdown('discussionType');
        foreach ($discussionTypes as $element) {
            $discussionType->addOption($element['id'], $element['type_name']);
        }
        $counter = 0;
        $objRadioButton = new radio('discussionType');
        $objRadioButton->setTableColumns(3);
        $objRadioButton->setBreakSpace('table');
        foreach ($discussionTypes as $element) {
            $objRadioButton->addOption($element['id'], htmlentities($element['type_name']));

            //$objRadioButton->extra = 'onclick="changeLabel();"';
        }

// TODO: Set to NULL and add client side validation
        if ($mode == 'fix') {
            $objRadioButton->setSelected($details['type']);
        } else {
            $objRadioButton->setSelected($discussionTypes[0]['id']);
        }
        // TODO: Set to NULL and add client side validation
        if ($mode == 'fix') {
            $objRadioButton->setSelected($details['type']);
        } else {
            $objRadioButton->setSelected($discussionTypes[0]['id']);
        }

        $addTable->addCell($objRadioButton->show());
        $addTable->endRow();
        // Show Sticky Topic
        if ($this->objUser->isCourseAdmin($this->contextCode)) {
            $addTable->startRow();
            $addTable->addCell($this->objLanguage->languageText('mod_discussion_stickytopic', 'discussion', 'Sticky Topic') . ':');

            $sticky = new radio('stickytopic');

            $sticky->addOption('1', $this->objLanguage->languageText('word_yes'));
            $sticky->addOption('0', $this->objLanguage->languageText('word_no'));
            $sticky->setSelected('0');
            $sticky->setBreakSpace(' &nbsp; ');
            $addTable->addCell($sticky->show());
            $addTable->endRow();
        } else {
            $sticky = new hiddeninput('stickytopic', 'no');
            $newTopicForm->addToForm($sticky->show());
        }
// Language

        $addTable->startRow();

        $languageLabel = new label($this->objLanguage->languageText('word_language', 'system') . ':', 'input_language');
        $addTable->addCell($languageLabel->show(), 120);
        $languageDropdown = new dropdown('language');
        $languageCodes = & $this->newObject('languagecode', 'language');
// Sort Associative Array by Language, not ISO Code
        $languageList = $languageCodes->iso_639_2_tags->codes;
        asort($languageList);

        foreach ($languageList as $key => $value) {
            $languageDropdown->addOption($key, $value);
        }
        if ($mode == 'fix') {
            $languageDropdown->setSelected($details['language']);
        } else {
            $languageDropdown->setSelected($languageCodes->getISO($this->objLanguage->currentLanguage()));
        }
        $addTable->addCell($languageDropdown->show());
        $addTable->endRow();

        $addTable->startRow();
        $htmlareaLabel = new label($this->objLanguage->languageText('word_message') . ':', 'message');

        if ($mode == 'fix') {
            $messageCSS = 'error';
        } else {
            $messageCSS = NULL;
        }
        $addTable->addCell($htmlareaLabel->show(), 120, 'top', NULL, $messageCSS);

        $editor = &$this->newObject('htmlarea', 'htmlelements');
        $editor->toolbarSet = 'simple';
        $editor->setName('message');
        if ($mode == 'fix' && isset($details['message'])) {
            $editor->setContent($details['message']);
        }

        $objContextCondition = &$this->getObject('contextcondition', 'contextpermissions');
        $this->isContextLecturer = $objContextCondition->isContextMember('Lecturers');
        $addTable->addCell($editor->show());

        $addTable->endRow();
        if ($discussion['attachments'] == 'Y') {
            $addTable->startRow();


            $attachmentsLabel = new label($this->objLanguage->languageText('mod_discussion_attachments', 'discussion') . ':', 'attachments');
            $addTable->addCell($attachmentsLabel->show(), 120);

            $form = new form('saveattachment', $this->uri(array('action' => 'saveattachment')));

            $objSelectFile = $this->newObject('selectfile', 'filemanager');
            $objSelectFile->name = 'attachment';
            $form->addToForm($objSelectFile->show());
            // Fix undefined variable error for $discussionId
            if (!isset($discussionId)) {
                $discussionId = "";
            }
            $hiddeninput = new hiddeninput('id', $discussionId);
            $form->addToForm($hiddeninput->show());

            $button = new button('save_attachment_button', 'Attach File');
            $button->cssClass = 'save';
            $button->extra = 'onclick="saveAttachment(this.parentNode)"';
            if (isset($files)) {
                if ((is_countable($files) ? count($files) : 0) > 0) {

                    foreach ($files AS $file) {
                        $icon = $objIcon->getDeleteIconWithConfirm($file['id'], array('action' => 'deleteattachment', 'id' => $file['id'], 'attachmentwindow' => $discussionId), 'discussion', 'Are you sure wou want to remove this attachment');
                        $link = '<li>' . $file['filename'] . ' ' . $icon . '</li>';
                        $form->addToForm($link);
                    }
                }
            }
            $hiddenDiscussionInput = new hiddeninput('discussion', $discussionId);
            $form->addToForm($hiddenDiscussionInput->show());

            $details = $this->getVar('details');
            $temporaryId = $details['temporaryId'];
            $hiddenTemporaryId = new hiddeninput('temporaryId', $temporaryId);
            $form->addToForm($hiddenTemporaryId->show());
            $addTable->addCell($form->show());
            $addTable->endRow();
        }

        if ($discussion['subscriptions'] == 'Y') {
            $addTable->startRow();
            $addTable->addCell($this->objLanguage->languageText('mod_discussion_emailnotification', 'discussion', 'Email Notification') . ':');
            $subscriptionsRadio = new radio('subscriptions');
            $subscriptionsRadio->addOption('nosubscriptions', $this->objLanguage->languageText('mod_discussion_donotsubscribetothread', 'discussion', 'Do not subscribe to this thread'));
            $subscriptionsRadio->addOption('topicsubscribe', $this->objLanguage->languageText('mod_discussion_notifytopic', 'discussion', 'Notify me via email when someone replies to this thread'));
            $subscriptionsRadio->addOption('discussionsubscribe', $this->objLanguage->languageText('mod_discussion_notifydiscussion', 'discussion', 'Notify me of ALL new topics and replies in this discussion.'));
            $subscriptionsRadio->setBreakSpace('<br />');

            $numTopicSubscriptions = $this->objTopicSubscriptions->getNumTopicsSubscribed($discussionId, $this->objUser->userId());
            $discussionSubscription = $this->objDiscussionSubscriptions->isSubscribedToDiscussion($discussionId, $this->objUser->userId());
            if ($discussionSubscription) {
                $subscriptionsRadio->setSelected('discussionsubscribe');
                $subscribeMessage = $this->objLanguage->languageText('mod_discussion_youaresubscribedtodiscussion', 'discussion', 'You are currently subscribed to the discussion, receiving notification of all new posts and replies.');
            } else {
                $subscriptionsRadio->setSelected('nosubscriptions');
                $subscribeMessage = $this->objLanguage->languageText('mod_discussion_youaresubscribedtonumbertopic', 'discussion', 'You are currently subscribed to [NUM] topics.');
                $subscribeMessage = str_replace('[NUM]', $numTopicSubscriptions, $subscribeMessage);
            }

            $div = '
    <div class="discussionTangentIndent">' . $subscribeMessage . '</div>';

            $addTable->addCell($subscriptionsRadio->show() . $div);
            $addTable->endRow();
        }

        $addTable->startRow();

        $addTable->addCell(' ');

        $submitButton = new button('submitform', $this->objLanguage->languageText('word_submit'));
        $submitButton->value = $this->objLanguage->languageText('word_send', 'system', 'Send');
        $submitButton->cssClass = 'button';
//$submitButton->setToSubmit();
        $submitButton->extra = ' onclick="SubmitForm()"';

        $cancelButton = new button('cancel', $this->objLanguage->languageText('word_cancel'));
        $cancelButton->cssClass = 'button chisimba-button-secondary';
        $returnUrl = $this->uri(array('action' => 'discussion', 'id' => $discussionId, 'type' => $discussiontype));
        $cancelButton->setOnClick("window.location='$returnUrl'");

        $addTable->addCell($submitButton->show() . ' ' . $cancelButton->show());

        $addTable->endRow();

        $newTopicForm->addToForm($js.$addTable->show());

//                $newTopicForm->addToForm($addTable->show());
        return $newTopicForm->show();
    }

    public function show() {
        return $this->buildModernEntryForm();
    }

    /**
     * Build the accessible topic composer without layout tables.
     *
     * @return string Rendered topic form.
     */
    private function buildModernEntryForm() {
        $escape = static function ($value) {
            return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
        };
        $discussionId = (string) $this->getParam('id');
        $discussion = $this->objDiscussion->getDiscussion($discussionId);
        if (!is_array($discussion) || empty($discussion['id'])) {
            return '<div class="chisimba-notice chisimba-notice--error" role="alert">The requested discussion could not be found.</div>';
        }
        $failure = in_array($this->getParam('message'), array('missing', 'savefailed'), true);
        $details = $failure ? $this->getSession($this->getParam('tempid')) : array();
        if (!is_array($details)) {
            $details = array();
        }
        $temporaryId = isset($details['temporaryId'])
            ? (string) $details['temporaryId']
            : $this->objUser->userId() . '_' . time();
        $selectedType = isset($details['type']) ? (string) $details['type'] : '';
        $discussionTypes = $this->objDiscussionType->getDiscussionTypes();
        if ($selectedType === '' && !empty($discussionTypes[0]['id'])) {
            $selectedType = (string) $discussionTypes[0]['id'];
        }

        $editor = $this->newObject('htmlarea', 'htmlelements');
        $editor->toolbarSet = 'simple';
        $editor->setName('message');
        if (isset($details['message'])) {
            $editor->setContent($details['message']);
        }
        $languageDropdown = new dropdown('language');
        $languageDropdown->cssClass = 'discussion-compose__language';
        $languageCodes = $this->newObject('languagecode', 'language');
        $languageList = $languageCodes->iso_639_2_tags->codes;
        asort($languageList);
        foreach ($languageList as $key => $value) {
            $languageDropdown->addOption($key, $value);
        }
        $languageDropdown->setSelected(isset($details['language'])
            ? $details['language']
            : $languageCodes->getISO($this->objLanguage->currentLanguage()));

        $error = '';
        if ($failure) {
            $errorText = $this->getParam('message') === 'savefailed'
                ? $this->objLanguage->languageText('mod_discussion_savefailed', 'discussion', 'Your topic could not be sent. Your work has been preserved; please try again.')
                : $this->objLanguage->languageText('mod_discussion_messageisblank', 'discussion');
            $error = '<div class="chisimba-notice chisimba-notice--error" role="alert"><strong>' . $escape($errorText) . '</strong></div>';
        }

        $typeOptions = '';
        foreach ($discussionTypes as $type) {
            $id = 'discussion-type-' . preg_replace('/[^a-zA-Z0-9_-]/', '-', (string) $type['id']);
            $checked = (string) $type['id'] === $selectedType ? ' checked' : '';
            $typeOptions .= '<label class="discussion-choice" for="' . $escape($id) . '"><input id="' . $escape($id)
                . '" type="radio" name="discussionType" value="' . $escape($type['id']) . '"' . $checked . '><span>'
                . $escape($type['type_name']) . '</span></label>';
        }
        $sticky = $this->objUser->isCourseAdmin($this->contextCode)
            ? '<fieldset class="discussion-compose__compact"><legend>Sticky topic</legend><label><input type="radio" name="stickytopic" value="1"> Yes</label><label><input type="radio" name="stickytopic" value="0" checked> No</label></fieldset>'
            : '<input type="hidden" name="stickytopic" value="0">';

        $attachments = '';
        if (($discussion['attachments'] ?? 'N') === 'Y') {
            $selectFile = $this->newObject('selectfile', 'filemanager');
            $selectFile->name = 'attachment';
            $selectFile->context = $this->contextCode !== 'root';
            $selectFile->widthOfInput = '100%';
            $attachments = '<div class="chisimba-form-field"><label>Attachment from File Manager</label><p class="discussion-compose__hint">Choose a file available in this course scope.</p><div class="discussion-file-picker">'
                . $selectFile->show() . '</div></div>';
        }

        $subscriptions = '';
        if (($discussion['subscriptions'] ?? 'N') === 'Y') {
            $isSubscribed = $this->objDiscussionSubscriptions->isSubscribedToDiscussion($discussionId, $this->objUser->userId());
            $subscriptions = '<fieldset><legend>Notifications</legend>'
                . '<label class="discussion-choice"><input type="radio" name="subscriptions" value="nosubscriptions"' . (!$isSubscribed ? ' checked' : '') . '><span>Do not subscribe</span></label>'
                . '<label class="discussion-choice"><input type="radio" name="subscriptions" value="topicsubscribe"><span>Notify me about replies to this topic</span></label>'
                . '<label class="discussion-choice"><input type="radio" name="subscriptions" value="discussionsubscribe"' . ($isSubscribed ? ' checked' : '') . '><span>Notify me about all activity in this discussion</span></label></fieldset>';
        }

        $action = $this->uri(array('module' => 'discussion', 'action' => 'savenewtopic', 'type' => $discussion['discussion_type']));
        $cancel = $this->uri(array('action' => 'discussion', 'id' => $discussionId, 'type' => $discussion['discussion_type']));
        return '<main class="chisimba-workspace chisimba-flow discussion-workspace"><header class="chisimba-page-header chisimba-card"><div><p class="chisimba-eyebrow">Discussion</p><h1>Start a new topic</h1><p>Share an idea, question or announcement in <strong>'
            . $escape($discussion['discussion_name']) . '</strong>.</p></div></header>' . $error
            . '<form id="newTopicForm" class="chisimba-card chisimba-form discussion-compose-modern" method="post" action="' . $escape($action) . '">'
            . '<input type="hidden" name="discussion" value="' . $escape($discussionId) . '"><input type="hidden" name="temporaryId" value="' . $escape($temporaryId) . '">'
            . '<div class="chisimba-form-field"><label for="title">Subject</label><input id="title" name="title" maxlength="160" value="' . $escape($details['title'] ?? '') . '" required autofocus></div>'
            . '<fieldset><legend>Topic type</legend><div class="discussion-choice-grid">' . $typeOptions . '</div></fieldset>'
            . $sticky
            . '<div class="chisimba-form-field discussion-compose__language-field"><label for="input_language">Language</label>' . $languageDropdown->show() . '</div>'
            . '<div class="chisimba-form-field"><label for="message">Message</label>' . $editor->show() . '</div>'
            . $attachments . $subscriptions
            . '<div class="chisimba-form-actions"><button class="button" type="submit">Send topic</button><a class="button chisimba-button-secondary" href="' . $escape($cancel) . '">Cancel</a></div></form></main>';
    }

}

?>
