<?php
/** Native controls shared by the modern and legacy topic views. @author Derek Keats */
if (empty($GLOBALS['kewl_entry_point_run'])) die('No direct access');
class postcontrols extends ChisimbaObject {
    public function show($post, $discussion) {
        if (!is_array($post) || !$this->getObject('discussionaccess', 'discussion')->mayRead($discussion)) return '';
        $user = $this->getObject('user', 'security');
        $lang = $this->getObject('language', 'language');
        $e = static fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
        $id = (string)$post['post_id'];
        // Flat stream rows omit topic state; resolve authoritative edit/lock metadata.
        $post = $this->getObject('dbpost', 'discussion')->getPostWithText($id, true);
        if (!is_array($post) || !hash_equals((string)$discussion['id'], (string)$post['discussion_id'])) return '';
        $open = !$this->getObject('dbdiscussion', 'discussion')->checkIfDiscussionLocked($post['discussion_id']) && ($post['status'] ?? '') !== 'CLOSE';
        $html = '';
        if ($open && $user->isLoggedIn() && empty($post['replypost']) && ($discussion['assessment_enabled'] ?? 'N') !== 'Y'
            && $this->getObject('dbpost', 'discussion')->checkOkToEdit($post['datelastupdated'], $post['userid'])) {
            $html .= '<a class="button chisimba-button-secondary" href="' . $e($this->uri(array('action'=>'editpost', 'id'=>$id), 'discussion')) . '">' . $e($lang->languageText('word_edit', 'system')) . '</a>';
        }
        if ($open && $user->isLoggedIn() && ($discussion['ratingsenabled'] ?? 'N') === 'Y') {
            $ratings = $this->getObject('dbpost_ratings', 'discussion')->selectPrepared('SELECT COALESCE(SUM(rating),0) AS total FROM tbl_discussion_post_ratings WHERE post_id = ?', array($id));
            $html .= '<span>' . $e($lang->languageText('mod_discussion_rating_total', 'discussion')) . ': ' . (int)$ratings[0]['total'] . '</span>';
            $csrf = $this->getObject('nativeauthwebcomposition', 'security')->build()['csrf'];
            foreach (array('savepostratingup'=>'mod_discussion_rate_up', 'savepostratingdown'=>'mod_discussion_rate_down') as $action=>$label) {
                $html .= '<form method="post" action="' . $e($this->uri(array('action'=>$action), 'discussion')) . '"><input type="hidden" name="post_id" value="' . $e($id) . '"><input type="hidden" name="csrf_token" value="' . $e($csrf->issue('disc_rate_' . hash('sha256', $id))) . '"><button class="button chisimba-button-secondary" type="submit">' . $e($lang->languageText($label, 'discussion')) . '</button></form>';
            }
        }
        $files = $this->getObject('dbfile', 'filemanager');
        $policy = $this->getObject('filereadpolicy', 'filemanager');
        foreach ((array)$this->getObject('dbpostattachments', 'discussion')->getAttachments($id) as $attachment) {
            $file = $files->getRow('id', $attachment['attachment_id']);
            if (!$policy->mayRead($file)) continue;
            $html .= '<a class="button chisimba-button-secondary" href="' . $e($this->uri(array('action'=>'downloadattachment', 'id'=>$attachment['id'], 'topic'=>$post['topic_id']), 'discussion')) . '">' . $e($attachment['filename']) . '</a>';
        }
        return $html;
    }
}
