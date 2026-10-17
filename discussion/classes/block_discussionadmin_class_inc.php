<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of block_discussionadmin_class
 *
 * @author monwabisi
 */
// security check - must be included in all scripts
if (!$GLOBALS['kewl_entry_point_run']) {
        die("You cannot view this page directly");
}

// end security check
include 'block_discussionlist_class_inc.php';

class block_discussionadmin extends ChisimbaObject {

        var $contextCode;
        var $contextObject;
        var $contextTitle;
        var $objLanguage;
        var $objDiscussion;
        var $contextDiscussions;

        //put your code here
        public function init() {
                $this->title = '';
                $this->loadClass('form', 'htmlelements');
                $this->loadClass('button', 'htmlelements');
                $this->loadClass('dropdown', 'htmlelements');
                $this->loadClass('label', 'htmlelements');
                $this->loadClass('link', 'htmlelements');
                // Get Context Code Settings
                $this->contextObject = & $this->getObject('dbcontext', 'context');
                // If not in context, set code to be 'root' called 'Lobby'
                $this->contextTitle = $this->contextObject->getTitle();
                $this->contextCode = $this->contextObject->getContextCode();
                if (trim((string) $this->contextCode) === '') {
                        $this->contextCode = 'root';
                        $this->contextTitle = 'Lobby';
                }
                $this->objLanguage = $this->getObject('language', 'language');
                // Discussion Classes
                $this->objDiscussion = & $this->getObject('dbdiscussion');
                $this->contextDiscussions = $this->objDiscussion->getAllContextDiscussions($this->contextCode);
        }

        public function show() {
                return $this->buildModernAdmin();
        }

        /**
         * Build a task-oriented, responsive discussion administration page.
         *
         * @return string Rendered administration workspace.
         */
        private function buildModernAdmin() {
                $escape = static function ($value) {
                        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
                };
                $icons = $this->getObject('iconservice', 'ui');
                $createUrl = $this->uri(array('module' => 'discussion', 'action' => 'creatediscussion'));
                $messageMap = array(
                        'discussioncreated' => 'Discussion created.',
                        'discussionupdated' => 'Discussion settings updated.',
                        'defaultdiscussionchanged' => 'Default discussion updated.',
                        'discussiondeleted' => 'Discussion deleted.'
                );
                $messageKey = (string) $this->getParam('message');
                if ($messageKey === 'assessmentsettingsfailed') {
                        $notice = '<div class="chisimba-notice chisimba-notice--error" role="alert">The discussion was saved, but its assessment settings could not be stored. Ask an administrator to apply the latest Discussion module update.</div>';
                } else {
                        $notice = isset($messageMap[$messageKey])
                                ? '<div class="chisimba-notice chisimba-notice--success" role="status">' . $escape($messageMap[$messageKey]) . '</div>'
                                : '';
                }

                $default = $this->objDiscussion->getDefaultDiscussion($this->contextCode);
                $defaultOptions = '';
                foreach ($this->contextDiscussions as $discussion) {
                        if (($discussion['discussion_visible'] ?? 'N') !== 'Y') {
                                continue;
                        }
                        $selected = ($default['id'] ?? '') === $discussion['id'] ? ' selected' : '';
                        $defaultOptions .= '<option value="' . $escape($discussion['id']) . '"' . $selected . '>' . $escape($discussion['discussion_name']) . '</option>';
                }
                if ($defaultOptions === '') {
                        $defaultForm = '<section class="chisimba-card discussion-admin-default discussion-admin-default--empty" aria-labelledby="discussion-default-heading">'
                                . '<div><h2 id="discussion-default-heading">Default discussion</h2><p>Create a discussion before choosing the default for this scope.</p></div>'
                                . '<span class="discussion-setting-badge discussion-setting-badge--off">'
                                . $icons->render('circle-dashed', array('decorative' => true)) . ' Not configured</span></section>';
                } else {
                        $defaultForm = '<form class="chisimba-card chisimba-form discussion-admin-default" method="post" action="'
                                . $escape($this->uri(array('module' => 'discussion', 'action' => 'setdefaultdiscussion'))) . '"><input type="hidden" name="csrf_token" value="' . $escape($this->getObject('nativeauthwebcomposition', 'security')->build()['csrf']->issue('discussion_manage')) . '"><div><h2>Default discussion</h2><p>This is the first discussion used when this scope needs a general conversation space.</p></div><div class="chisimba-form-field discussion-admin-default__field"><label for="discussion-default">Use as default</label><select id="discussion-default" name="discussion">'
                                . $defaultOptions . '</select></div><div class="chisimba-form-actions"><button class="button chisimba-button-compact" type="submit">'
                                . $icons->render('save', array('decorative' => true)) . '<span>Save default</span></button></div></form>';
                }

                $cards = '';
                foreach ($this->contextDiscussions as $discussion) {
                        $isDefault = ($discussion['defaultdiscussion'] ?? 'N') === 'Y';
                        $viewUrl = $this->uri(array('module' => 'discussion', 'action' => 'discussion', 'id' => $discussion['id']));
                        $editUrl = $this->uri(array('module' => 'discussion', 'action' => 'editdiscussion', 'id' => $discussion['id']));
                        $markUrl = $this->uri(array('module' => 'discussion', 'action' => 'markdiscussion', 'id' => $discussion['id']));
                        $deleteUrl = $this->uri(array('module' => 'discussion', 'action' => 'deletediscussion', 'id' => $discussion['id']));
                        $flags = array(
                                'Visible' => ($discussion['discussion_visible'] ?? 'N') === 'Y',
                                'Open for posting' => ($discussion['discussionlocked'] ?? 'N') !== 'Y',
                                'Student topics' => ($discussion['studentstarttopic'] ?? 'N') === 'Y',
                                'Attachments' => ($discussion['attachments'] ?? 'N') === 'Y',
                                'Notifications' => ($discussion['subscriptions'] ?? 'N') === 'Y',
                                'Ratings' => ($discussion['ratingsenabled'] ?? 'N') === 'Y'
                        );
                        $badges = '';
                        foreach ($flags as $label => $enabled) {
                                $badges .= '<span class="discussion-setting-badge ' . ($enabled ? 'discussion-setting-badge--on' : 'discussion-setting-badge--off') . '">'
                                        . $icons->render($enabled ? 'check' : 'minus', array('decorative' => true)) . ' ' . $escape($label) . '</span>';
                        }
                        $archive = ($discussion['archivedate'] ?? '') && $discussion['archivedate'] !== '0000-00-00'
                                ? '<p class="discussion-admin-card__archive">Shows topics from ' . $escape($discussion['archivedate']) . ' onward.</p>'
                                : '';
                        $delete = $isDefault ? '' : '<a class="chisimba-icon-button chisimba-button-danger" href="' . $escape($deleteUrl) . '" aria-label="Delete ' . $escape($discussion['discussion_name']) . '" title="Delete">'
                                . $icons->render('trash-2', array('decorative' => true)) . '</a>';
                        $cards .= '<article class="chisimba-card discussion-admin-card"><header><div><div class="discussion-admin-card__title"><h2><a href="' . $escape($viewUrl) . '">'
                                . $escape($discussion['discussion_name']) . '</a></h2>' . ($isDefault ? '<span class="discussion-default-badge">Default</span>' : '')
                                . '</div><p>' . $escape(strip_tags((string) ($discussion['discussion_description'] ?? ''))) . '</p></div></header><div class="discussion-setting-list" aria-label="Discussion settings">'
                                . $badges . '</div>' . $archive . '<div class="chisimba-form-actions discussion-admin-card__actions"><a class="chisimba-icon-button" href="' . $escape($viewUrl) . '" aria-label="Open ' . $escape($discussion['discussion_name']) . '" title="Open discussion">'
                                . $icons->render('messages-square', array('decorative' => true)) . '</a>' . (($discussion['assessment_enabled'] ?? 'N') === 'Y' ? '<a class="chisimba-icon-button" href="' . $escape($markUrl) . '" aria-label="Mark ' . $escape($discussion['discussion_name']) . '" title="Mark discussion">' . $icons->render('graduation-cap', array('decorative' => true)) . '</a>' : '') . '<a class="chisimba-icon-button" href="' . $escape($editUrl) . '" aria-label="Edit ' . $escape($discussion['discussion_name']) . ' settings" title="Edit settings">'
                                . $icons->render('settings', array('decorative' => true)) . '</a>' . $delete . '</div></article>';
                }
                if ($cards === '') {
                        $cards = '<section class="chisimba-card discussion-empty-state"><div class="discussion-empty-state__icon">' . $icons->render('messages-square', array('decorative' => true)) . '</div><div><h2>No discussions yet</h2><p>Create the first discussion for this scope.</p></div></section>';
                }

                return '<main class="chisimba-workspace chisimba-flow discussion-workspace"><header class="chisimba-page-header chisimba-card"><div><p class="chisimba-eyebrow">Administration</p><h1>Discussions</h1><p>Manage the places where people exchange ideas, questions and feedback in ' . $escape($this->contextTitle ?: 'this scope') . '.</p></div><a class="button chisimba-button-compact" href="'
                        . $escape($createUrl) . '">' . $icons->render('plus', array('decorative' => true)) . '<span>Create discussion</span></a></header>'
                        . $notice . $defaultForm . '<section class="discussion-admin-grid" aria-label="Available discussions">' . $cards . '</section></main>';
        }

}

?>
