<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of block_postreply_class_inc
 *
 * @author monwabisi
 */

// security check - must be included in all scripts
if (!$GLOBALS['kewl_entry_point_run']) {
        die("You cannot view this page directly");
}

// end security check
class block_postreply extends ChisimbaObject {

    var $objUser;
    var $objPost;
    var $objTopic;
    var $objDiscussion;
    var $objContextObject;
    var $contextCode;
    var $objLanguage;
    var $objDiscussionRatings;
    var $objPostRatings;

    //put your code here
    function init() {
        $this->title = '';

        $this->objUser = $this->getObject('user', 'security');
        $this->objPost = $this->getObject('dbpost', 'discussion');
        $this->objTopic = $this->getObject('dbtopic', 'discussion');
        $this->objDiscussion = $this->getObject('dbdiscussion', 'discussion');
        // Get Context Code Settings
        $this->contextObject = & $this->getObject('dbcontext', 'context');
        $this->contextCode = $this->contextObject->getContextCode();
        $this->objLanguage = $this->getObject('language', 'language');
        // Discussion Ratings
        $this->objDiscussionRatings = & $this->getObject('dbdiscussion_ratings');
        $this->objPostRatings = & $this->getObject('dbpost_ratings');
        $this->csrf = $this->getObject('nativeauthwebcomposition', 'security')->build()['csrf'];
    }

    function buildForm() {
        $escape = static fn($value) => htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
        $postID = trim((string)$this->getParam('id'));
        $post = $this->objPost->getPostWithText($postID);
        if (!is_array($post)) {
            return '<main class="chisimba-workspace"><section class="chisimba-card discussion-empty-state"><h1>Post unavailable</h1><p>The contribution you wanted to reply to could not be found.</p></section></main>';
        }
        $discussion = $this->objDiscussion->getDiscussion($post['discussion_id']);
        if (!is_array($discussion)) {
            return '<main class="chisimba-workspace"><section class="chisimba-card discussion-empty-state"><h1>Discussion unavailable</h1></section></main>';
        }
        $topic = $this->objTopic->getTopicDetails($post['topic_id']);
        $topicTitle = (string)($topic['post_title'] ?? $post['post_title'] ?? 'Discussion topic');
        $parentTitle = trim((string)($post['post_title'] ?? ''));
        $defaultTitle = stripos($parentTitle, 'Re:') === 0 ? $parentTitle : 'Re: ' . $parentTitle;
        $details = array();
        $error = '';
        if ($this->getParam('message') === 'missing') {
            $saved = $this->getSession($this->getParam('tempid'));
            if (is_array($saved)) {
                $details = $saved;
                $defaultTitle = (string)($saved['title'] ?? $defaultTitle);
            }
            $error = '<p class="chisimba-notice chisimba-notice--error" role="alert">Write a message before sending your reply.</p>';
        }
        $languageCodes = $this->getObject('languagecode', 'language');
        $language = $languageCodes->getISO($this->objLanguage->currentLanguage());
        $editor = $this->newObject('htmlarea', 'htmlelements');
        $editor->setName('message');
        $editor->setContent((string)($details['message'] ?? ''));
        $editor->setRows(12);
        $editor->setColumns('100');
        $editor->context = $this->contextCode !== 'root';
        $author = trim((string)($post['firstname'] ?? '') . ' ' . (string)($post['surname'] ?? ''));
        if ($author === '') {
            $author = (string)($post['username'] ?? 'a participant');
        }
        $excerpt = trim(preg_replace('/\s+/', ' ', strip_tags((string)($post['post_text'] ?? ''))));
        if (strlen($excerpt) > 360) {
            $excerpt = substr($excerpt, 0, 357) . '…';
        }
        $type = trim((string)$this->getParam('type', $discussion['discussion_type'] ?? 'context')) ?: 'context';
        $topicUrl = $this->uri(array('action' => 'viewtopic', 'id' => $post['topic_id'], 'type' => $type));
        $action = $this->uri(array('action' => 'savepostreply', 'type' => $type));
        $token = $this->csrf->issue('disc_reply_' . hash('sha256', $postID));
        return '<main class="chisimba-workspace chisimba-flow discussion-workspace discussion-reply">'
            . '<header class="chisimba-page-header chisimba-card"><div><p class="chisimba-eyebrow">Discussion reply</p><h1>Reply to ' . $escape($author) . '</h1><p>In <a href="' . $escape($topicUrl) . '">' . $escape($topicTitle) . '</a></p></div></header>'
            . $error
            . '<aside class="chisimba-card discussion-reply-context" aria-label="Contribution being replied to"><p><strong>' . $escape($author) . '</strong> wrote</p><p>' . $escape($excerpt) . '</p></aside>'
            . '<form id="postReplyForm" class="chisimba-card chisimba-form discussion-compose-modern" method="post" action="' . $escape($action) . '">'
            . '<input type="hidden" name="csrf_token" value="' . $escape($token) . '"><input type="hidden" name="parent" value="' . $escape($postID) . '">'
            . '<div class="chisimba-form-field"><label for="posttitle">Subject</label><input id="posttitle" name="posttitle" maxlength="160" value="' . $escape($defaultTitle) . '" required></div>'
            . '<input type="hidden" name="lang" value="' . $escape($language) . '">'
            . '<div class="chisimba-form-field"><label for="message">Message</label>' . $editor->show() . '</div>'
            . '<div class="chisimba-form-actions"><button class="button" type="submit">Send reply</button><a class="button chisimba-button-secondary" href="' . $escape($topicUrl) . '">Cancel</a></div>'
            . '</form></main>';
        /* Legacy form retained below for reference until the remaining inline reply callers are retired. */
        //get the recordid
//        $post_id = $this->getParam('id');
//        $objHighlightLabels = $this->getObject('highlightlabels', 'htmlelements');
//        $postReplyForm = new form('postReplyForm', $this->uri(array('action' => 'savepostreply', 'type' => $discussiontype)));
//        $postReplyForm->displayType = 3;
//        $postReplyForm->addRule('title', $this->objLanguage->languageText('mod_discussion_addtitle', 'discussion'), 'required');
//
//        $addTable = $this->getObject('htmltable', 'htmlelements');
//        $addTable->width = '99%';
//        $addTable->align = 'center';
//        $addTable->cellpadding = 10;
//        $addTable->startRow();
//        $subjectLabel = new label($this->objLanguage->languageText('word_subject', 'system') . ':', 'input_title');
//        $addTable->addCell($subjectLabel->show(), 100);
//        
//        echo $objHighlightLabels->show();
//        // Get the Post
//        $post = $this->objPost->getPostWithText($post_id);
//        // Get details of the Discussion
//        $discussion = $this->objDiscussion->getDiscussion($post['discussion_id']);
//        // Check if Title has Re: attached to it   
//        if (substr($post['post_title'], 0, 3) == 'Re:') {
//            // If it does, simply strip slashes
//            $defaultTitle = stripslashes($post['post_title']);
//            $originalTitle = stripslashes($post['post_title']);
//        } else {
//            // Else strip slashes AND append Re: to the title
//            $defaultTitle = 'Re: ' . stripslashes($post['post_title']);
//            $originalTitle = 'Re: ' . stripslashes($post['post_title']);
//        }
//        // If result of server-side validation, change default title to posted one
//        if ($mode == 'fix') {
//            // Select Posted Title
//            $defaultTitle = $details['title'];
//        }
//
//        $details = "";
//        $mode = "";
//        // Check if form is a result of server-side validation or not'
//        if ($this->getParam('message') == 'missing') {
//            $details = $this->getSession($this->getParam('tempid'));
//            $this->setVarByRef('details', $details);
//            $temporaryId = $details['temporaryId'];
//            $mode = 'fix';
//        } else {
//            $temporaryId = $this->objUser->userId() . '_' . mktime();
//            $mode = 'new';
//        }
        $js='
<script type="text/javascript">
    //<![CDATA[

    function SubmitForm()
    {
        document.forms["postReplyForm"].submit();
    }

    //]]>
</script>
';
        return $this->objPost->showPostReplyForm($postID).$js;
    }

    function show() {
        return $this->buildForm();
    }

}

?>
