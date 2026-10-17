<?php
/** Native post editing; drafts survive validation, expiry and concurrent edits. */
if (!$GLOBALS['kewl_entry_point_run']) die('You cannot view this page directly');
$e = static fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$t = fn($key) => $this->objLanguage->languageText($key, 'discussion');
$p = $editPostData;
$draft = is_array($editPostDraft) ? $editPostDraft : array('title'=>$p['post_title'], 'message'=>$p['post_text']);
$editor = $this->newObject('htmlarea', 'htmlelements');
$editor->name = 'message';
$editor->height = '220px';
$editor->setContent($draft['message']);
echo '<main class="chisimba-workspace"><section class="chisimba-form-card chisimba-form-card--wide"><h1>' . $e($t('mod_discussion_editposttitle')) . '</h1>';
echo $this->getObject('contextualhelp', 'help')->show('discussion', 'editpost');
if ($this->getParam('message') === 'editretry') echo '<p class="chisimba-notice chisimba-notice--error" role="alert">' . $e($t('mod_discussion_edit_retry')) . '</p>';
echo '<form method="post" action="' . $e($this->uri(array('action'=>'updatepost'))) . '"><input type="hidden" name="post_id" value="' . $e($p['post_id']) . '"><input type="hidden" name="csrf_token" value="' . $e($editPostToken) . '"><input type="hidden" name="revision" value="' . $e(hash('sha256', (string)$p['post_title'] . "\0" . (string)$p['post_text'])) . '"><div class="chisimba-form-field"><label for="post-title">' . $e($t('mod_discussion_posttitle')) . '</label><input id="post-title" name="title" maxlength="160" required value="' . $e($draft['title']) . '"></div><div class="chisimba-form-field">' . $editor->show() . '</div><div class="chisimba-form-actions"><button class="button" type="submit">' . $e($this->objLanguage->languageText('word_save', 'system')) . '</button><a class="button chisimba-button-secondary" href="' . $e($this->uri(array('action'=>'viewtopic','id'=>$p['topic_id']))) . '">' . $e($this->objLanguage->languageText('word_cancel', 'system')) . '</a></div></form></section></main>';
