<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of block_createedit_class_inc
 *
 * @author monwabisi
 * @author Derek Keats
 */
// security check - must be included in all scripts
if (!$GLOBALS['kewl_entry_point_run']) {
        die("You cannot view this page directly");
}

// end security check
class block_createedit extends ChisimbaObject {

    var $objLanguage;
    var $contextTitle;
    var $contextCode;
    var $contextObject;
    var $objDiscussion;

    //put your code here
    public function init() {
        $this->setVar('pageSuppressXML', true);
        $this->loadClass('form', 'htmlelements');
        $this->loadClass('textinput', 'htmlelements');
        $this->loadClass('button', 'htmlelements');
        $this->loadClass('label', 'htmlelements');
        $this->loadClass('radio', 'htmlelements');
        $this->loadClass('htmlheading', 'htmlelements');
        $this->title = '';
        $this->objDiscussion = $this->getObject('dbdiscussion', 'discussion');
        // Get Context Code Settings
        $this->contextObject = & $this->getObject('dbcontext', 'context');
        $this->objLanguage = $this->getObject('language', 'language');
        // If not in context, set code to be 'root' called 'Lobby'
        $this->contextTitle = $this->contextObject->getTitle();
        // Get Context Code Settings
        $this->contextObject = & $this->getObject('dbcontext', 'context');
        $this->contextCode = $this->contextObject->getContextCode();

        // If not in context, set code to be 'root' called 'Lobby'
        $this->contextTitle = $this->contextObject->getTitle();
        if (trim((string) $this->contextCode) === '') {
            $this->contextCode = 'root';
            $this->contextTitle = 'Lobby';
        }
    }

    function biuldForm() {
        // Get Context Code Settings
        $contextObject = & $this->getObject('dbcontext', 'context');
//        $contextCode = $contextObject->getContextCode();

        // If not in context, set code to be 'root' called 'Lobby'
        $contextTitle = $contextObject->getTitle();
        $id = $this->getParam('id');
        $script = "<script language='JavaScript' type='text/javascript'>
//<![CDATA[
if(!document.getElementById && document.all) {
    document.getElementById = function(id){ return document.all[id]}
}


    function toggleArchiveInput()
    {
        // alert(document.forms['myForm']);
        if (document.forms['myForm'].archivingRadio[1].checked)
            {
                showhide('dateSelect', 'visible');
            } else{
                showhide('dateSelect', 'hidden');
            }

    }

    function showhide (id, visible)
    {
        var style = document.getElementById(id).style
        style.visibility = visible;
    }
//]]>
</script>";
        $html =  $script;
        $this->setVar('pageSuppressXML', true);

        $objHighlightLabels = $this->getObject('highlightlabels', 'htmlelements');
        $html .=  $objHighlightLabels->show();


        $discussion = $this->objDiscussion->getDiscussion($id);
        // Check if Discussion exists
        if (!$discussion == false) {
            $action = 'editdiscussion';
        }

        $header = new htmlheading();
        $header->type = 3;

        $action = $this->getParam('action');
        if ($action == 'editdiscussion') {
            $header->str = $this->objLanguage->languageText('mod_discussion_editdiscussionsettings', 'discussion') . ': ' . $discussion['discussion_name'];
            $formAction = 'editdiscussionsave';
        } else {
            $header->str = $this->objLanguage->languageText('mod_discussion_createNewDiscussion', 'discussion', 'Create New Discussion') . ': ' . $contextTitle;
            $formAction = 'savediscussion';
        }
        if ($action == 'creatediscussion') {
            $html .= $header->show();
        }

        $form = new form('myForm', $this->uri(array('module' => 'discussion', 'action' => $formAction,'id'=>$id)));
        $form->displayType = 3;

        $table = $this->getObject('htmltable', 'htmlelements');
        $table->width = '80%';
        $table->cellpadding = 10;


// --------- New Row ---------- //

        $table->startRow();
        $nameLabel = new label($this->objLanguage->languageText('mod_discussion_nameofdiscussion', 'discussion') . ':', 'input_name');
        $table->addCell('<strong>' . $nameLabel->show() . '</strong>', 120);

        $nameInput = new textinput('name');
        $nameInput->size = 57;
        $nameInput->extra = ' maxlength="50"';

        if ($action == 'editdiscussion') {
            $nameInput->value = $discussion['discussion_name'];
        }

        $table->addCell($nameInput->show(), null, null, null, null, ' colspan="3"');

        $table->endRow();

// --------- New Row ---------- //

        $table->startRow();
        $nameLabel = new label($this->objLanguage->languageText('word_description', 'system') . ':', 'input_description');
        $table->addCell('<strong>' . $nameLabel->show() . '</strong>', 100);

        $nameInput = new textinput('description');
        $nameInput->size = 100;
        $nameInput->extra = 'maxlength="255"';
        if ($action == 'editdiscussion') {
            $nameInput->value = $discussion['discussion_description'];
        }
        $table->addCell($nameInput->show(), null, null, null, null, ' colspan="3"');

        $table->endRow();

// --------- New Row ---------- //

        if ($action == 'editdiscussion') {

            $table->startRow();

            $table->addCell('<strong>' . $this->objLanguage->languageText('mod_discussion_lockdiscussion', 'discussion') . '</strong>');

            $radioGroup = new radio('lockdiscussion');
            $radioGroup->setBreakSpace(' / ');

            // The option NO comes before YES - as no is this preferred
            $radioGroup->addOption('N', 'No');
            $radioGroup->addOption('Y', $this->objLanguage->languageText('word_yes', 'system'));

            $radioGroup->setSelected($discussion['discussionlocked']);

            $message = ' - ' . $this->objLanguage->languageText('mod_discussion_explainlocking', 'discussion') . '.';

            $table->addCell($radioGroup->show() . $message, null, null, null, null, ' colspan="3"');

            $table->endRow();
        }


// --------- New Row - Visibility & Rating Discussions ---------- //

        $table->startRow();
        $title = '<nobr>' . $this->objLanguage->languageText('mod_discussion_visible', 'discussion') . ':</nobr>';
        $table->addCell('<strong>' . $title . '</strong>', 100);

        if ($action == 'editdiscussion' && $discussion['defaultdiscussion'] == 'Y') {
            $hiddenIdInput = new textinput('visible');
            $hiddenIdInput->fldType = 'hidden';
            $hiddenIdInput->value = 'default';

            $table->addCell($this->objLanguage->languageText('mod_discussion_defaultdiscussion', 'discussion') . $hiddenIdInput->show());
        } else {
            $radioGroup = new radio('visible');
            $radioGroup->setBreakSpace('&nbsp;&nbsp;');
            $radioGroup->addOption('Y', $this->objLanguage->languageText('word_yes'));
            $radioGroup->addOption('N', $this->objLanguage->languageText('word_no'));

            if ($action == 'editdiscussion') {
                $radioGroup->setSelected($discussion['discussion_visible']);
            } else {
                $radioGroup->setSelected('Y');
            }

            $table->addCell($radioGroup->show());
        }


        $title = '<nobr><strong>' . $this->objLanguage->languageText('mod_discussion_usersrateposts', 'discussion') . ':</strong></nobr>';
        $table->addCell($title, 100);

        $radioGroup = new radio('ratings');
        $radioGroup->setBreakSpace('&nbsp;&nbsp;');
        $radioGroup->addOption('Y', $this->objLanguage->languageText('word_yes', 'system'));
        $radioGroup->addOption('N', $this->objLanguage->languageText('word_no', 'system'));
        if ($action == 'editdiscussion') {
            $radioGroup->setSelected($discussion['ratingsenabled']);
        } else {
            $radioGroup->setSelected('Y');
        }

        $table->addCell($radioGroup->show());
        $table->endRow();

// --------- New Row - Students start Topics & upload attachments ---------- //

        $table->startRow();
        $title = '<nobr><strong>' . ucwords($this->objLanguage->code2Txt('mod_discussion_studentsstartTopics', 'discussion')) . ':</strong></nobr>';
        $table->addCell($title, 100);

        $radioGroup = new radio('student');
        $radioGroup->setBreakSpace('&nbsp;&nbsp;');
        $radioGroup->addOption('Y', $this->objLanguage->languageText('word_yes', 'system', 'Yes'));
        $radioGroup->addOption('N', $this->objLanguage->languageText('word_no', 'system', 'No'));
        if ($action == 'editdiscussion') {
            $radioGroup->setSelected($discussion['studentstarttopic']);
        } else {
            $radioGroup->setSelected('Y');
        }

        $table->addCell($radioGroup->show());
        $title = '<nobr><strong>' . $this->objLanguage->languageText('mod_discussion_usersuploadattachments', 'discussion') . ':</strong></nobr>';
        $table->addCell($title, 100);

        $radioGroup = new radio('attachments');
        $radioGroup->setBreakSpace('&nbsp;&nbsp;');
        $radioGroup->addOption('Y', $this->objLanguage->languageText('word_yes', 'system', 'Yes'));
        $radioGroup->addOption('N', $this->objLanguage->languageText('word_no', 'system', 'No'));
        if ($action == 'editdiscussion') {
            $radioGroup->setSelected($discussion['attachments']);
        } else {
            $radioGroup->setSelected('Y');
        }

        $table->addCell($radioGroup->show());
        $table->endRow();

// --------- New Row - Subscriptions ---------- //

        $table->startRow();
        $title = '<nobr><strong>' . $this->objLanguage->languageText('mod_discussion_enableemailsubscription', 'discussion') . ':</strong></nobr>';
        $table->addCell($title, 100);

        $radioGroup = new radio('subscriptions');
        $radioGroup->setBreakSpace('&nbsp;&nbsp;');
        $radioGroup->addOption('Y', $this->objLanguage->languageText('word_yes', 'system', 'Yes'));
        $radioGroup->addOption('N', $this->objLanguage->languageText('word_no', 'system'));
        if ($action == 'editdiscussion') {
            $radioGroup->setSelected($discussion['subscriptions']);
        } else {
            $radioGroup->setSelected('Y');
        }

        $table->addCell($radioGroup->show());

        $table->addCell('&nbsp;');
        $table->addCell('&nbsp;');
        $table->endRow();


// --------- End Row ---------- //
// --------- New Row ---------- //

        if ($action == 'editdiscussion') {
            $table->startRow();

            $table->addCell('<strong><nobr>' . $this->objLanguage->languageText('mod_discussion_archivelabel', 'discussion') . ':</nobr></strong>', 100);

            $radioGroup = new radio('archivingRadio');
            $radioGroup->setBreakSpace(' / ');

            // The option NO comes before YES - as no is this preferred
            $radioGroup->addOption('N', $this->objLanguage->languageText('word_no', 'system', 'No'));
            $radioGroup->addOption('Y', $this->objLanguage->languageText('word_yes', 'system', 'Yes'));
            $radioGroup->extra = 'onclick="toggleArchiveInput()"';

            $selectDateLink = $this->newObject('datepicker', 'htmlelements');
            $selectDateLink->setName('archivedate');

            if ($discussion['archivedate'] == '' || $discussion['archivedate'] == '0000-00-00') {
                $radioGroup->setSelected('N');
                $selectDateLink->setDefaultDate(date('Y-m-d'));
            } else {
                $radioGroup->setSelected('Y');
                $selectDateLink->setDefaultDate($discussion['archivedate']);
            }


            $cell = $radioGroup->show() . ' <span id="dateSelect"> - ' . $selectDateLink->show() . ' <br /><span class="warning">' . $this->objLanguage->languageText('mod_discussion_archivewarning', 'discussion') . '</span></span>';
            $table->addCell($cell, null, null, null, null, ' colspan="3"');

            $table->endRow();
        }

// --------- End Row ---------- //
        $submitButton = new button('submitbtn', $this->objLanguage->languageText('word_save'));
        $submitButton->cssClass = 'save';
        $submitButton->setToSubmit();

        $cancelButton = new button('cancel', $this->objLanguage->languageText('word_cancel'));
        $returnUrl = $this->uri(array('action' => 'administration'));
        $cancelButton->setOnClick("window.location='$returnUrl'");

        $table->addCell($submitButton->show() . '&nbsp;&nbsp;&nbsp;&nbsp;' . $cancelButton->show(), null, null, null, null, ' colspan="4"');

        if ($action == 'editdiscussion') {
            $hiddenIdInput = new textinput('id');
            $hiddenIdInput->fldType = 'hidden';
            $hiddenIdInput->value = $discussion['id'];
            $form->addToForm($hiddenIdInput->show());
        }

        $form->addToForm($table->show());

        $form->addRule('name', $this->objLanguage->languageText('mod_discussion_discussionnameneeded', 'discussion'), 'required');
        $form->addRule('description', $this->objLanguage->languageText('mod_discussion_discussiondescriptionneeded', 'discussion'), 'required');

        $html .='<div class="creatediscussion">' . $form->show() . '</div>';
        $this->appendArrayVar('bodyOnLoad', 'toggleArchiveInput();');
        return $html;
    }

    /**
     * Render the modern create/edit workspace.
     *
     * @return string Accessible, responsive discussion settings form.
     */
    private function buildModernForm() {
        $escape = static function ($value) {
            return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
        };
        $icons = $this->getObject('iconservice', 'ui');
        $csrf = $this->getObject('nativeauthwebcomposition', 'security')->build()['csrf'];
        $token = $csrf->issue('discussion_manage');
        $id = trim((string) $this->getParam('id', ''));
        $editing = (string) $this->getParam('action') === 'editdiscussion';
        $discussion = $editing ? $this->objDiscussion->getDiscussion($id) : array();
        if (!is_array($discussion)) {
            $discussion = array();
            $editing = false;
        }
        $value = static function ($key, $default = '') use ($discussion) {
            return isset($discussion[$key]) ? (string) $discussion[$key] : $default;
        };
        $checked = static function ($actual, $expected) {
            return strtoupper((string) $actual) === $expected ? ' checked' : '';
        };
        $setting = static function ($name, $label, $help, $current) use ($escape, $checked) {
            return '<fieldset class="discussion-setting-control"><legend>' . $escape($label)
                . '</legend><p>' . $escape($help) . '</p><div class="discussion-choice-row">'
                . '<label><input type="radio" name="' . $escape($name) . '" value="Y"'
                . $checked($current, 'Y') . '> Yes</label><label><input type="radio" name="'
                . $escape($name) . '" value="N"' . $checked($current, 'N') . '> No</label></div></fieldset>';
        };

        $title = $editing ? 'Edit discussion' : 'Create discussion';
        $intro = $editing
            ? 'Update how this discussion works for ' . ($this->contextTitle ?: 'this scope') . '.'
            : 'Create a focused place for ideas, questions and feedback in ' . ($this->contextTitle ?: 'this scope') . '.';
        $formAction = $editing ? 'editdiscussionsave' : 'savediscussion';
        $actionUrl = $this->uri(array('module' => 'discussion', 'action' => $formAction));
        $cancelUrl = $this->uri(array('module' => 'discussion', 'action' => 'administration'));
        $settings = '';
        if ($editing) {
            $settings .= $setting('lockdiscussion', 'Lock discussion', 'Keep existing content readable but prevent new posts.', $value('discussionlocked', 'N'));
        }
        if ($editing && $value('defaultdiscussion', 'N') === 'Y') {
            $settings .= '<fieldset class="discussion-setting-control"><legend>Visible</legend><p>The default discussion must remain visible.</p><input type="hidden" name="visible" value="Y"><span class="discussion-setting-badge discussion-setting-badge--on">'
                . $icons->render('check', array('decorative' => true)) . ' Yes</span></fieldset>';
        } else {
            $settings .= $setting('visible', 'Visible', 'Make this discussion available to people in this scope.', $value('discussion_visible', 'Y'));
        }
        $settings .= $setting('student', 'Students can start topics', 'Allow learners to begin new conversations.', $value('studentstarttopic', 'Y'));
        $settings .= $setting('attachments', 'Attachments', 'Allow files to be added to posts.', $value('attachments', 'Y'));
        $settings .= $setting('subscriptions', 'Notifications', 'Allow email and notification subscriptions.', $value('subscriptions', 'Y'));
        $settings .= $setting('ratings', 'Post ratings', 'Allow participants to rate posts.', $value('ratingsenabled', 'N'));

        $archive = '';
        if ($editing) {
            $archiveDate = $value('archivedate');
            $archiving = $archiveDate !== '' && $archiveDate !== '0000-00-00' ? 'Y' : 'N';
            $archive = '<fieldset class="discussion-setting-control discussion-setting-control--wide"><legend>Archive date</legend><p>Optionally hide topics older than this date.</p><div class="discussion-choice-row"><label><input type="radio" name="archivingRadio" value="N"'
                . $checked($archiving, 'N') . '> Do not archive</label><label><input type="radio" name="archivingRadio" value="Y"'
                . $checked($archiving, 'Y') . '> Archive older topics</label></div><label class="discussion-date-field" for="discussion-archive-date">Show topics from<input id="discussion-archive-date" type="date" name="archivedate" value="'
                . $escape($archiving === 'Y' ? $archiveDate : date('Y-m-d')) . '"></label></fieldset>';
        }

        $hidden = '<input type="hidden" name="csrf_token" value="' . $escape($token) . '">';
        if ($editing) {
            $hidden .= '<input type="hidden" name="id" value="' . $escape($id) . '">';
        }
        return '<main class="chisimba-workspace chisimba-flow discussion-workspace discussion-editor"><header class="chisimba-page-header chisimba-card"><div><p class="chisimba-eyebrow">Discussion administration</p><h1>'
            . $escape($title) . '</h1><p>' . $escape($intro) . '</p></div></header><form class="chisimba-card chisimba-form discussion-editor__form" method="post" action="'
            . $escape($actionUrl) . '">' . $hidden . '<section class="discussion-editor__identity" aria-labelledby="discussion-details-heading"><h2 id="discussion-details-heading">Discussion details</h2><div class="chisimba-form-field"><label for="discussion-name">Name</label><input id="discussion-name" name="name" type="text" maxlength="50" required value="'
            . $escape($value('discussion_name')) . '" autocomplete="off"><p class="chisimba-field-help">Use a short name that tells people what belongs here.</p></div><div class="chisimba-form-field"><label for="discussion-description">Description</label><textarea id="discussion-description" name="description" rows="6" maxlength="2000" required>'
            . $escape($value('discussion_description')) . '</textarea><p class="chisimba-field-help">Explain the purpose of this discussion. Plain text only.</p></div></section><section aria-labelledby="discussion-settings-heading"><h2 id="discussion-settings-heading">Participation and access</h2><div class="discussion-settings-grid">'
            . $settings . $archive . '</div></section><div class="chisimba-form-actions discussion-editor__actions"><button class="button chisimba-button-compact" type="submit">'
            . $icons->render('save', array('decorative' => true)) . '<span>' . ($editing ? 'Save changes' : 'Create discussion') . '</span></button><a class="button chisimba-button-secondary chisimba-button-compact" href="'
            . $escape($cancelUrl) . '">' . $icons->render('x', array('decorative' => true)) . '<span>Cancel</span></a></div></form></main>';
    }

    /** @return string Rendered block content. */
    public function show() {
        return $this->buildModernForm();
    }

}

?>
