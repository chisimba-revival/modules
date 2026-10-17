<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of block_discussionside_class_inc
 *
 * @author monwabisi
 */

// security check - must be included in all scripts
if (!$GLOBALS['kewl_entry_point_run']) {
        die("You cannot view this page directly");
}

// end security check
class block_discussionside extends ChisimbaObject {

        var $contextObject;
        var $contextCode;
        var $objPost;
        var $objUser;
        var $objContextUser;
        var $objSysConfig;
        var $objDiscussion;
        var $objTopic;

        //put your code here
        function init() {
                $this->loadClass('link', 'htmlelements');
                $this->title = "Discussion Side block";
                // Get Context Code Settings
                $this->contextObject = & $this->getObject('dbcontext', 'context');
                $this->contextCode = $this->contextObject->getContextCode();
                $this->objPost = $this->getObject('dbpost', 'discussion');
                $this->objUser = $this->getObject('user', 'security');
                $this->objContextUser = $this->getObject('usercontext', 'context');
                $this->objSysConfig = $this->getObject('dbsysconfig', 'sysconfig');
                $this->objDiscussion = $this->getObject('dbdiscussion', 'discussion');
                $this->objTopic = $this->getObject('dbtopic', 'discussion');
        }

        function buildForm() {
                $context = trim((string)$this->contextCode) ?: 'root';
                if ($context !== 'root' && !$this->getObject('courseawarelaunchservice', 'context')->mayUseActiveCourse($this->objUser->userId(), 'discussion')) return '';
                $e = static fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
                $html = '';
                foreach ((array)$this->objDiscussion->getContextDiscussions($context) as $discussion) {
                        if (!$this->getObject('discussionaccess', 'discussion')->mayRead($discussion)) continue;
                        $html .= '<details><summary>' . $e($discussion['discussion_name']) . '</summary><ul>';
                        foreach ((array)$this->objTopic->showTopicsInDiscussion($discussion['id'], $this->objUser->userId()) as $topic) {
                                $url = $this->uri(array('module'=>'discussion','action'=>'viewtopic','id'=>$topic['topic_id']));
                                $html .= '<li><a href="' . $e($url) . '">' . $e(stripslashes((string)$topic['post_title'])) . '</a></li>';
                        }
                        $html .= '</ul></details>';
                }
                return $html;
        }

        function show() {
                return $this->buildForm();
        }

}

?>
