<?php

// security check - must be included in all scripts
if (!$GLOBALS['kewl_entry_point_run']) {
        die("You cannot view this page directly");
}

$this->loadClass('link', 'htmlelements');

/**
 * Discussion Posts Table
 * This class controls all functionality relating to the tbl_discussion_post table
 * @author Tohir Solomons
 * @copyright (c) 2004 University of the Western Cape
 * @package discussion
 * @version 1
 */

/**
 * This class returns arrays of recordset from the database table 'tbl_discussion_post' and also helps with the display of topics
 */
class dbPost extends dbTable {

    // Services and state populated during initialisation.
    public $objUserPic;
    public $objSkin;
    public $trimstrObj;
    public $dbPostratings;
    public $objDiscussion;
    public $objPostText;
    public $objUser;
    public $userId;
    public $objDateFunctions;
    public $objLanguageCode;
    public $objLanguage;
    public $objDiscussionSubscriptions;
    public $objTopicSubscriptions;
    public $objTranslatedDate;
    public $objDateTime;
    public $contextObject;
    public $contextCode;
    public $objWashoutFilters;
    public $objIcon;
    public $objScriptClear;
    public $objSysConfig;
    public $showFullName;


        /**
         *  $var Boolean Variable to flag whether to show the reply-to-post link
         */
        var $repliesAllowed = TRUE;

        /**
         *  $var Boolean Variable to flag whether to show the edit-a-post link
         */
        var $editingPostsAllowed = TRUE;

        /**
         *  $var First level of Indentration
         */
        var $threadDisplayLevel = 1;

        /**
         *  $var Counter to count the number of open div tags (for the collapsible thread system)
         */
        var $numOpenThreadDisplayDivs = 0;

        /**
         *  $var Boolean Variable to flag whether to a discussion is locked - affects replying to a post/editing/translating
         */
        var $discussionLocked = FALSE;

        /**
         *  $var Array containing values for the rating of discussions
         */
        var $discussionRatingsArray;

        /**
         *  $var Boolean Variable to flag whether to show the ratings drop down
         */
        var $showRatings = FALSE;

        /**
         *  $var String Variable to hold the type of discussion - either context or workgroup
         */
        var $discussiontype = 'context';

        /**
         * @var object To handle all post attachments
         */
        var $objPostAttachments;
        var $showModeration;

        /**
         *
         * @var object
         */
        var $objFileIcons;

        /**
         *
         * @var object To preview post attachments
         */
        var $objFilePreview;

        /**
         *
         * @var object object to be used to retrive post ratings
         */
        var $dbjPostratings;

        /**
         * Constructor method to define the table(default)
         */
        function init($tableName = null, $pearDb = null, $errorCallback = 'globalPearErrorHandler') {
                parent::init('tbl_discussion_post');
                $this->objUserPic = $this->getObject('imageupload', 'useradmin');
                $this->objSkin = $this->getObject('skin', 'skin');
                $this->trimstrObj = $this->getObject('trimstr', 'strings');
                $this->objFileIcons = $this->newObject('fileicons', 'files');
                $this->objFilePreview = $this->getObject('filepreview', 'filemanager');
                $this->dbPostratings = $this->getObject('dbpost_ratings', 'discussion');

                $this->objDiscussion = $this->getObject('dbdiscussion');
                $this->objPostAttachments = $this->getObject('dbpostattachments');
                $this->objPostText = $this->getObject('dbposttext');

                $this->objUser = $this->getObject('user', 'security');

                $this->userId = $this->objUser->userId();

                $this->objDateFunctions = $this->getObject('dateandtime', 'utilities');
                $this->objLanguageCode = $this->getObject('languagecode', 'language');
                $this->objLanguage = $this->getObject('language', 'language');

                // Load Discussion Subscription classes
                $this->objDiscussionSubscriptions = $this->getObject('dbdiscussionsubscriptions');
                $this->objTopicSubscriptions = $this->getObject('dbtopicsubscriptions');

                $this->objTranslatedDate = $this->getObject('translatedatedifference', 'utilities');
                $this->objDateTime = $this->getObject('dateandtime', 'utilities');

                // Get Context Code Settings
                $this->contextObject = $this->getObject('dbcontext', 'context');
                $this->contextCode = $this->contextObject->getContextCode();

                $this->objWashoutFilters = $this->getObject('washout', 'utilities');

                $this->objIcon = $this->newObject('geticon', 'htmlelements');

                $this->loadClass('link', 'htmlelements');

                $this->objScriptClear = $this->getObject('script', 'utilities');

                $this->showModeration = FALSE;

                $this->objSysConfig = $this->getObject('dbsysconfig', 'sysconfig');

                $this->showFullName = $this->objSysConfig->getValue('SHOWFULLNAME', 'discussion');

                if ($this->showFullName == '') {
                        $this->showFullName = TRUE;
                }

                if ($this->showFullName != FALSE) {
                        $this->showFullName = TRUE;
                }
        }

        /**
         * Insert a post into the database
         *
         * @param string $post_parent: Record ID of the Parent Post - that the user is replying to
         * @param string $post_tangent_parent: Record ID of the Tangent Post - that the user is replying to
         * @param string $post_title: Title of Post
         * @param string $post_message: Text of the Post
         * @param string $topic_id: Record ID of the Topic
         * @param string $userId: User ID of person posting the post
         * @param string $dateLastUpdated: Date Post was made
         * @param string $id Id - Optional, used by API
         * @return string $this->getLastInsertId()
         */
        function insertSingle($post_parent, $post_tangent_parent, $discussion_id, $topic_id, $userId, $level = 1, $id = NULL) {
                // Interim measure. Alternative, use regexp and replace with space
                //$post_title = strip_tags($post_title);
                //echo $post_parent;

                if ($post_parent == '0') {
                        $lastRightPointer = $this->getLastRightPointer($discussion_id);
                        $leftPointer = $lastRightPointer + 1;
                        $rightPointer = $lastRightPointer + 2;
                        $level = 1;
                } else {
                        $lastRightPointer = $this->getPostRightPointer($post_parent);
                        $updateRightSQL = 'UPDATE tbl_discussion_post SET rght = rght + 2 WHERE rght > ' . ($lastRightPointer - 1);
                        $this->query($updateRightSQL);
                        $updateLeftSQL = 'UPDATE tbl_discussion_post SET lft = lft + 2 WHERE lft > ' . ($lastRightPointer - 1);
                        $this->query($updateLeftSQL);

                        $leftPointer = $lastRightPointer;
                        $rightPointer = $lastRightPointer + 1;
                        $level += 1;
                }

                $now = date('Y-m-d H:i:s');
                return $this->insert(array(
                    'id' => $id,
                    'post_parent' => $post_parent,
                    'post_tangent_parent' => $post_tangent_parent,
                    'topic_id' => $topic_id,
                    'post_order' => $this->getLastPostOrder($topic_id),
                    'userId' => $userId,
                    'modifierid' => $userId,
                    'lft' => $leftPointer,
                    'rght' => $rightPointer,
                    'level' => $level,
                    'datecreated' => $now,
                    'datelastupdated' => $now
                ));
        }

        /**
         * This method gets the last right pointer in a discussion. Used when a new topic is post,
         * i.e. pointers for topic will be: left = lastright+1; right = lastright+2
         * @param $discussion Discussion Id to get the last right pointer from
         */
        function getLastRightPointer($discussion) {
                $sql = 'SELECT tbl_discussion_post.rght FROM tbl_discussion_topic  INNER JOIN tbl_discussion_post ON (tbl_discussion_post.topic_id = tbl_discussion_topic.id) WHERE tbl_discussion_topic.discussion_id = \'' . $discussion . '\' ORDER BY rght DESC LIMIT 1';

                $list = $this->getArray($sql);

                if ((is_countable($list) ? count($list) : 0) == 0) {
                        return 0;
                } else {
                        return $list[0]['rght'];
                }
        }

        /**
         * Method to get the Left and Right Pointer for the first post in a topic.
         * @param string $topic_id Record Id of the topic
         * @return array Left and Right pointer of first post in a topic
         */
        function getTopicPointer($topic_id) {
                $sql = 'SELECT lft, rght FROM tbl_discussion_post WHERE topic_id=\'' . $topic_id . '\' AND post_parent = \'0\' ORDER BY level LIMIT 1';

                $array = $this->getArray($sql);

                if ((is_countable($array) ? count($array) : 0) == 0) {
                        return NULL;
                } else {
                        return $array[0];
                }
        }

        /**
         * Post Order is the order of post with regards to date, irrespective of level.
         * This method gets the last Post Order by providng a topic id
         * @param $topic Record Id of the Topic
         * @return int Order Number of last post +1 (Plus One)
         */
        function getLastPostOrder($topic_id) {
                $sql = 'SELECT post_order FROM tbl_discussion_post WHERE topic_id=\'' . $topic_id . '\' ORDER BY post_order DESC LIMIT 1';
                $array = $this->getArray($sql);

                if ((is_countable($array) ? count($array) : 0) == 0) {
                        return 1;
                } else {
                        return $array[0]['post_order'] + 1;
                }
        }

        /**
         * Method to get the right pointer of a post
         * @param $post_id Record Id of the Post
         * @return int Right Pointer Value
         */
        function getPostRightPointer($post_id) {
                $sql = 'SELECT rght FROM tbl_discussion_post WHERE id=\'' . $post_id . '\' LIMIT 1';
                $array = $this->getArray($sql);

                if ((is_countable($array) ? count($array) : 0) == 0) {
                        return NULL;
                } else {
                        return $array[0]['rght'];
                }
        }

        /**
         * Method to get a thread for a topic by providing the left and right pointers
         * @param int $leftPointer Left Pointer Value
         * @param int $rightPointer Right Pointer Value
         * @param string $topic_id Record Id of the Topic
         *
         * CHECK!! This can be changed to just provide the topic - internal check for pointer
         * @return array List of Posts
         */
        function getThread($topic_id) {
                $pointers = $this->getTopicPointer($topic_id);

                $sql = 'SELECT tbl_discussion_post.*, tbl_discussion_post_text.*, tbl_users.firstname, tbl_users.surname, tbl_users.username,
        tbl_discussion_post_attachment.attachment_id, replypost.id AS replypost, languagecheck.id AS anotherlanguage, tbl_discussion_post_ratings.rating FROM tbl_discussion_post
        INNER JOIN tbl_discussion_post_text ON (tbl_discussion_post.id = tbl_discussion_post_text.post_id AND tbl_discussion_post_text.original_post = "1")
        LEFT JOIN tbl_users ON (tbl_discussion_post.userId = tbl_users.userId OR tbl_discussion_post.userId = tbl_users.id)
        LEFT JOIN tbl_discussion_post_attachment ON (tbl_discussion_post.id = tbl_discussion_post_attachment.post_id)
        LEFT JOIN tbl_discussion_post AS replypost ON (tbl_discussion_post.id = replypost.post_parent)
        LEFT JOIN tbl_discussion_post_text AS languagecheck ON (tbl_discussion_post.id = languagecheck.post_id AND languagecheck.original_post="0" AND tbl_discussion_post_text.language != languagecheck.language)
        LEFT JOIN tbl_discussion_post_ratings ON (tbl_discussion_post.id = tbl_discussion_post_ratings.post_id)
        WHERE tbl_discussion_post.topic_id = \'' . $topic_id . '\' AND tbl_discussion_post.lft >= \'' . $pointers['lft'] . '\' AND tbl_discussion_post.rght <= \'' . $pointers['rght'] . '\'
        GROUP BY tbl_discussion_post.id ORDER BY lft';

                return $this->getArray($sql);
        }

        /**
         * Method to get the list of posts in a topic arranged by the order they were made.
         * Could be ordered by Date
         * @param string $topic Record Id of the Topic
         * @return array List of Posts
         */
        function getFlatThread($topic) {
                $sql = 'SELECT tbl_discussion_post.*,  tbl_discussion_post_text.*, tbl_users.firstname, tbl_users.surname, tbl_users.username,
        tbl_discussion_post_attachment.attachment_id, replypost.id AS replypost, languagecheck.id AS anotherlanguage, tbl_discussion_post_ratings.rating
        FROM tbl_discussion_post
        INNER JOIN tbl_discussion_post_text ON (tbl_discussion_post.id = tbl_discussion_post_text.post_id  AND tbl_discussion_post_text.original_post = \'1\')
        LEFT JOIN tbl_users ON (tbl_discussion_post.userId = tbl_users.userId OR tbl_discussion_post.userId = tbl_users.id)
        LEFT JOIN tbl_discussion_post_attachment ON (tbl_discussion_post.id = tbl_discussion_post_attachment.post_id)
        LEFT JOIN tbl_discussion_post AS replypost ON (tbl_discussion_post.id = replypost.post_parent)
        LEFT JOIN tbl_discussion_post_text AS languagecheck ON (tbl_discussion_post.id = languagecheck.post_id AND languagecheck.original_post=\'0\' AND tbl_discussion_post_text.language != languagecheck.language)
        LEFT JOIN tbl_discussion_post_ratings ON (tbl_discussion_post.id = tbl_discussion_post_ratings.post_id)
        WHERE tbl_discussion_post.topic_id = \'' . $topic . '\' GROUP BY tbl_discussion_post.id ORDER BY post_order';

                return $this->getArray($sql);
        }

        /**
         * Method to get the Root (First) Post in a topic: title, text, date, etc.
         * @param string $topic Record Id of the topic
         * @return array Details of the post
         */
        function getRootPost($topic) {
                $sql = 'SELECT tbl_discussion_post.*, tbl_discussion_topic.*,  tbl_discussion_post_text.*, discussion_name, discussion_id, tbl_users.firstname, tbl_users.surname, tbl_users.username,
        tbl_discussion_post_attachment.attachment_id, replyPost.id AS replypost, languagecheck.id AS anotherlanguage,
        tbl_discussion_post_ratings.rating, tbl_discussion_post.lft as postleft, tbl_discussion_post.rght as postright
        FROM tbl_discussion_post INNER JOIN tbl_discussion_post_text ON (tbl_discussion_post.id = tbl_discussion_post_text.post_id AND tbl_discussion_post_text.original_post=\'1\')
        INNER JOIN tbl_discussion_topic ON (tbl_discussion_topic.id = tbl_discussion_post.topic_id)
        INNER JOIN tbl_discussion ON (tbl_discussion.id = tbl_discussion_topic.discussion_id)
        LEFT JOIN tbl_users ON (tbl_discussion_post.userId = tbl_users.userId OR tbl_discussion_post.userId = tbl_users.id)
        LEFT JOIN tbl_discussion_post_attachment ON (tbl_discussion_post.id = tbl_discussion_post_attachment.post_id)
        LEFT JOIN tbl_discussion_post AS replyPost ON (tbl_discussion_post.id = replyPost.post_parent)
        LEFT JOIN tbl_discussion_post_text AS languagecheck ON (tbl_discussion_post.id = languagecheck.post_id AND languagecheck.original_post=\'0\' AND tbl_discussion_post_text.language != languagecheck.language)
        LEFT JOIN tbl_discussion_post_ratings ON (tbl_discussion_post.id = tbl_discussion_post_ratings.post_id)
        WHERE tbl_discussion_post.topic_id=\'' . $topic . '\' AND tbl_discussion_post.post_parent = \'0\'
        GROUP BY tbl_discussion_post.id  LIMIT 1'; //AND tbl_discussion_post.level = "1"

                $results = $this->getArray($sql);

                if ((is_countable($results) ? count($results) : 0) == 0) {
                        return NULL;
                } else {
                        return $results[0];
                }
        }

        /**
         * Method to get the title, text, date, etc of any post, by providing the record id of the post.
         * @param string $post Record Id of the post
         * @return array Details of the post
         */
        function getPostWithText($post, $originalOnly = false) {
                $sql = 'SELECT tbl_discussion_post.*, tbl_discussion_post_text.*, tbl_discussion_topic.*, tbl_discussion_post.userid AS userid, tbl_users.firstname, tbl_users.surname, tbl_users.username, tbl_discussion_post.datelastupdated AS datelastupdated, tbl_discussion_post_attachment.attachment_id, replyPost.id AS replypost, languagecheck.id AS anotherlanguage, tbl_discussion_post_ratings.rating, tbl_discussion_post.lft as postleft, tbl_discussion_post.rght as postright
        FROM tbl_discussion_post
        INNER JOIN tbl_discussion_post_text ON (tbl_discussion_post.id = tbl_discussion_post_text.post_id )
        INNER JOIN tbl_discussion_topic ON (tbl_discussion_post.topic_id = tbl_discussion_topic.id)
        LEFT JOIN tbl_users ON (tbl_discussion_post.userId = tbl_users.userId OR tbl_discussion_post.userId = tbl_users.id)
        LEFT JOIN tbl_discussion_post_attachment ON (tbl_discussion_post.id = tbl_discussion_post_attachment.post_id)
        LEFT JOIN tbl_discussion_post AS replyPost ON (tbl_discussion_post.id = replyPost.post_parent)
        LEFT JOIN tbl_discussion_post_text AS languagecheck ON (tbl_discussion_post.id = languagecheck.post_id  AND tbl_discussion_post_text.language != languagecheck.language)
        LEFT JOIN tbl_discussion_post_ratings ON (tbl_discussion_post.id = tbl_discussion_post_ratings.post_id)
        WHERE tbl_discussion_post_text.post_id = ?' . ($originalOnly ? ' AND tbl_discussion_post_text.original_post = 1' : '') . ' GROUP BY tbl_discussion_post.id LIMIT 1';

                $results = $this->selectPrepared($sql, array($post));

                if ((is_countable($results) ? count($results) : 0) == 0) {
                        return NULL;
                } else {
                        return $results[0];
                }
        }

        /**
         * Method to get a post in a language
         * At the moment, it works with the record_id not language!
         * @param string $postTextId Record Id of the post text
         * @return array Details of the post
         */
        function getPostInLanguage($postTextId) {
                $sql = 'SELECT tbl_discussion_post.*, tbl_discussion_post_text.*, tbl_discussion_topic.*, tbl_discussion_post.userid AS userid, tbl_users.firstname, tbl_users.surname, tbl_discussion_post.datelastupdated AS datelastupdated, tbl_discussion_post_attachment.attachment_id, replyPost.id AS replypost, languagecheck.id AS anotherlanguage
        FROM tbl_discussion_post
        INNER JOIN tbl_discussion_post_text ON (tbl_discussion_post.id = tbl_discussion_post_text.post_id )
        INNER JOIN tbl_discussion_topic ON (tbl_discussion_post.topic_id = tbl_discussion_topic.id)
        LEFT JOIN tbl_users ON (tbl_discussion_post.userId = tbl_users.userId OR tbl_discussion_post.userId = tbl_users.id)
        LEFT JOIN tbl_discussion_post_attachment ON (tbl_discussion_post.id = tbl_discussion_post_attachment.post_id)
        LEFT JOIN tbl_discussion_post AS replyPost ON (tbl_discussion_post.id = replyPost.post_parent)
        LEFT JOIN tbl_discussion_post_text AS languagecheck ON (tbl_discussion_post.id = languagecheck.post_id AND tbl_discussion_post_text.language != languagecheck.language)
        WHERE tbl_discussion_post_text.id = \'' . $postTextId . '\' GROUP BY tbl_discussion_post.id LIMIT 1';


                //return array('post_text'=>$sql);
                $results = $this->getArray($sql);

                if ((is_countable($results) ? count($results) : 0) == 0) {
                        return NULL;
                } else {
                        return $results[0];
                }
        }

        /**
         * Method to display a post in a formatted fashion
         * @param array $post The post with all information in an array -> text, title, date, author, etc.
         * @param boolean $showMargin A flag to indicate whether the post should be shown with indented margins (threaded margin)
         * @param string $topic_id ID of the topic the reply belongs to
         * @param boolean $makeContractible A flag to indicate whether to include javascript to expand/contract threads
         * @return string The post in a formatted version.
         */
        function displayPost($post, $showMargin = FALSE, $makeContractible = FALSE) {
                if (!is_array($post)) return '';
                $e = static fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
                $id = (string)($post['post_id'] ?? '');
                $metadata = $this->getPostWithText($id, true);
                if (!is_array($metadata)) return '';
                $discussion = $this->getObject('dbdiscussion', 'discussion')->getDiscussion($metadata['discussion_id']);
                if (!$this->getObject('discussionaccess', 'discussion')->mayRead($discussion)) return '';
                $actions = $this->getObject('postcontrols', 'discussion')->show($post, $discussion);
                if ($this->repliesAllowed && $this->objUser->isLoggedIn()) {
                        $actions .= '<a class="button chisimba-button-secondary" href="' . $e($this->uri(array('action'=>'postreply','id'=>$id), 'discussion')) . '">' . $e($this->objLanguage->languageText('word_reply', 'system')) . '</a>';
                }
                if ($this->showModeration) {
                        $actions .= '<a class="button chisimba-button-secondary" href="' . $e($this->uri(array('action'=>'moderatepost','id'=>$id), 'discussion')) . '">' . $e($this->objLanguage->languageText('mod_discussion_moderatepost', 'discussion')) . '</a>';
                }
                return '<article class="chisimba-card discussion-post" id="post-' . $e($id) . '"><header><h2>' . $e(stripslashes((string)($post['post_title'] ?? ''))) . '</h2><p>' . $e(trim(($post['firstname'] ?? '') . ' ' . ($post['surname'] ?? ''))) . '</p></header><div class="discussion-post__content">' . $this->renderSafePostText($post['post_text'] ?? '') . '</div><footer class="chisimba-form-actions">' . $actions . '</footer></article>';
        }

        /** Return stored post markup through Discussion's established script and washout filters. */
        public function renderSafePostText($text) {
                return $this->objWashoutFilters->parseText(
                        $this->objScriptClear->removeScript(stripslashes((string)$text))
                );
        }

        /**
         * This function checks whether it is OK for the user to edit his/her post
         * Criteria includes:
         * - User had to post this post
         * - Post has to be under 30 minutes old
         *
         * In this module, we also check that No One has replied to the post yet, but that check is done elsewhere with SQL
         *
         * @param $postDate Date the Post was made
         * @param $postUser User who made the Post
         *
         * @return boolean True if it is ok to edit| False
         */
        function checkOkToEdit($postDate, $postUser) {
                $created = strtotime((string)$postDate);
                $age = $created === false ? -1 : time() - $created;
                return $age >= 0 && $age < 1800 && hash_equals((string)$postUser, (string)$this->userId);
        }

        /**
         * Method to Display a thread with indentations
         * @param string $topic_id Record Id of the Topic
         * @return string Thread
         */
        function displayThread($topic_id) {
                $posts = $this->getThread($topic_id);

                $thread = '';

                if ((is_countable($posts) ? count($posts) : 0) == 1) {
                        $showExpandable = FALSE;
                } else {
                        $showExpandable = TRUE;
                }

                foreach ($posts AS $post) {
                        $thread .= $this->displayPost($post, TRUE, $showExpandable);
                }

                // Close any open Divs
                if ($this->numOpenThreadDisplayDivs > 0) {
                        for ($i = 1; $i <= $this->numOpenThreadDisplayDivs; $i++) {
                                $thread .= '</div>';
                        }
                }
                return $thread;
        }

        /**
         * Method to display a topic in a flat format
         * @param string $topic_id Record Id of the topic
         * @return Formatted thread ready for display.
         */
        function displayFlatThread($topic_id) {
                $posts = $this->getFlatThread($topic_id);

                $thread = '';

                if ((is_countable($posts) ? count($posts) : 0) == 1) {
                        $showExpandable = FALSE;
                } else {
                        $showExpandable = FALSE;
                }

                foreach ($posts AS $post) {
                        $thread .= $this->displayPost($post, FALSE, $showExpandable, $topic_id);
                }

                // Close any open Divs
                if ($this->numOpenThreadDisplayDivs > 0) {
                        for ($i = 1; $i <= $this->numOpenThreadDisplayDivs; $i++) {
                                $thread .= '</div>';
                        }
                }
                return $thread;
        }

        /**
         * Method to get all posts in a topic, and convert them into a tree-like format
         * @param string $topic_id Record Id of the Topic
         * @param string $highlightPostId Record Id of the Post to highlight
         * @return string The entire tree in a string format.
         */
        function generateTopicPostsTree($topic_id, $highlightPostId) {
                // Get all posts for the topic
                $threadArray = $this->getThread($topic_id);

                $this->loadClass('treemenu', 'tree');
                $this->loadClass('treenode', 'tree');
                $this->loadClass('dhtml', 'tree');
                $this->loadClass('htmllist', 'tree');

                if ($threadArray[0]['post_id'] == $highlightPostId) {
                        $highlightPost = TRUE;
                } else {
                        $highlightPost = FALSE;
                }

                $treeMenu = new treemenu();
                $rootNode = new treenode($this->generateNodeText($threadArray[0], $highlightPost));

                // start an array
                $nodeArray = array();

                // Reference the array element with the record id
                $nodeArray[$threadArray[0]['post_id']] = & $rootNode;

                // Remove the first item
                array_shift($threadArray);

                // In the discussion's case, each topic has only one root.
                // This approach of adding children is done in that way.
                // Loop through the posts
                foreach ($threadArray as $thread) {
                        if ($thread['post_id'] == $highlightPostId) {
                                $highlightPost = TRUE;
                        } else {
                                $highlightPost = FALSE;
                        }

                        $thisnode = new treenode($this->generateNodeText($thread, $highlightPost));
                        $nodeArray[$thread['post_parent']]->addItem($thisnode);

                        // Reference the array element with the record id
                        $nodeArray[$thread['post_id']] = & $thisnode;
                }

                $treeMenu->addItem($rootNode);

                $tree = new htmllist($treeMenu, array('images' => $this->objSkin->getSkinURL() . 'treeimages/imagesAlt2'), FALSE);
                //$this->appendArrayVar('headerParams', '<script src="modules/tree/resources/TreeMenu.js" language="JavaScript" type="text/javascript"></script>');

                return $tree->getMenu();
        }

        /**
         * Method to generate a tree node text for the discussion
         * Has more to do with the formatting of the array.
         * @param array $post Post with all the fields in an array
         * @param boolean $highlightPost Flag on whether to highlight the post or not
         * @return array Properly formatted array ready to be used as a node
         */
        function generateNodeText($post, $highlightPost = FALSE) {
                $text = stripslashes($this->trimstrObj->strTrim(strip_tags(trim($post['post_text'])), 60));

                // Additional Filter to remove line breaks
                $text = str_replace("\r\n", ' ', $text);

                if ($this->objDateTime->formatDateOnly($post['datelastupdated']) == date('j F Y')) {
                        $datefield = $this->objLanguage->languageText('mod_discussion_todayat', 'discussion') . ' ' . $this->objDateTime->formatTime($post['datelastupdated']);
                } else {
                        $datefield = $this->objDateTime->formatDateOnly($post['datelastupdated']) . ' - ' . $this->objDateTime->formatTime($post['datelastupdated']);
                }

                if ($highlightPost) {
                        $cssClass = 'confirm';

                        if ($this->showFullName) {
                                $treenode = '<strong> ' . $post['firstname'] . ' ' . $post['surname'] . '</strong> -  <em>' . trim($text) . '</em> (' . $datefield . ')';
                        } else {
                                $treenode = '<strong> ' . $post['username'] . '</strong> -  <em>' . trim($text) . '</em> (' . $datefield . ')';
                        }
                } else {
                        $cssClass = NULL;
                        $link = & $this->getObject('link', 'htmlelements');
                        $link->href = $this->uri(array('action' => 'viewtopic', 'id' => $post['topic_id'], 'post' => $post['post_id']));
                        $link->link = $text;

                        if ($this->showFullName) {
                                $treenode = '<strong> ' . $post['firstname'] . ' ' . $post['surname'] . '</strong> -  <em>' . $link->show() . '</em> (' . $datefield . ')';
                        } else {
                                $treenode = '<strong> ' . $post['username'] . '</strong> -  <em>' . $link->show() . '</em> (' . $datefield . ')';
                        }
                }

                $icon = & $this->getObject('geticon', 'htmlelements');
                $icon->setIcon('gotopost', NULL, 'modules/discussion');

                return array('text' => $treenode, 'cssClass' => $cssClass, 'icon' => 'gotopost.gif');
        }

        /**
         * Method to get the details of the last post in a discussion
         * @param string discussion Record Id of the discussion
         * @return array
         */
        function getLastPost($discussion) {
                $sql = 'SELECT tbl_discussion_post_text. * , tbl_discussion_post.topic_id, tbl_users.firstname, tbl_users.surname, tbl_users.username
        FROM tbl_discussion_post INNER JOIN tbl_discussion_post_text ON (tbl_discussion_post_text.id = (
            SELECT latest_text.id FROM tbl_discussion_post_text AS latest_text
            WHERE latest_text.post_id = tbl_discussion_post.id
            ORDER BY latest_text.original_post DESC, latest_text.dateLastUpdated DESC LIMIT 1
        ))
        INNER JOIN tbl_discussion_topic ON ( tbl_discussion_post.topic_id = tbl_discussion_topic.id )
        LEFT  JOIN tbl_users ON ( tbl_discussion_post.userId = tbl_users.userId )
        WHERE tbl_discussion_topic.discussion_id = \'' . $discussion . '\'
        ORDER BY tbl_discussion_post.datelastupdated DESC LIMIT 1';

                $results = $this->getArray($sql);

                if ((is_countable($results) ? count($results) : 0) == 1) {
                        return $results[0];
                } else {
                        return FALSE;
                }
        }

        /**
         * gets the last n posts in a discussion
         * @param type $discussion
         * @param type $limit
         * @return type 
         */
        function getLastNPosts($discussion, $limit = 10) {
                $sql = 'SELECT distinct tbl_discussion_post_text. * , tbl_discussion_post.topic_id, tbl_users.firstname, tbl_users.surname, tbl_users.username
        FROM tbl_discussion_post INNER JOIN tbl_discussion_post_text ON ( tbl_discussion_post_text.post_id = tbl_discussion_post.id AND tbl_discussion_post_text.original_post=\'1\')
        INNER JOIN tbl_discussion_topic ON ( tbl_discussion_post.topic_id = tbl_discussion_topic.id )
        LEFT  JOIN tbl_users ON ( tbl_discussion_post.userId = tbl_users.userId )
        WHERE tbl_discussion_topic.discussion_id = \'' . $discussion . '\'
        ORDER BY tbl_discussion_post.datelastupdated DESC LIMIT 10';

                return $this->getArray($sql);
        }

        /**
         * Method to get the last 10 posts in a workgroup
         * All formatting is done here
         * @param string $workgroup Record Id of the Workgroup
         * @param string $content Context Code of the Workgroup
         * @param int $limit Number of Records to get
         */
        function getWorkGroupPosts($workgroup, $context, $limit = 10) {
                // Get the workgroups discussion record id
                $workgroupDiscussion = $this->objDiscussion->getWorkgroupDiscussion($context, $workgroup);

                // If none exists, return a message saying no records
                // no discussion is automatically created here. left until someone actually visits the discussion
                if ($workgroupDiscussion == NULL) {
                        return '<div class="noRecordsMessage">' . $this->objLanguage->languageText('mod_discussion_nopostsindiscussion', 'discussion') . '</div>';
                } else {
                        // If discussion exists, get the last '10' posts or whatever limit is specified
                        $sql = 'SELECT tbl_discussion_post_text. * , tbl_discussion_post.topic_id, tbl_users.firstname, tbl_users.surname
            FROM tbl_discussion_post INNER JOIN tbl_discussion_post_text ON ( tbl_discussion_post_text.post_id = tbl_discussion_post.id AND tbl_discussion_post_text.original_post=\'1\')
            INNER JOIN tbl_discussion_topic ON ( tbl_discussion_post.topic_id = tbl_discussion_topic.id )
            LEFT  JOIN tbl_users ON ( tbl_discussion_post.userId = tbl_users.userId )
            WHERE tbl_discussion_topic.discussion_id = \'' . $workgroupDiscussion . '\'
            ORDER BY tbl_discussion_post.dateLastUpdated DESC LIMIT ' . $limit;

                        $results = $this->getArray($sql);

                        // If there are no posts, return a message saying no recordss
                        if ((is_countable($results) ? count($results) : 0) == 0) {
                                return '<div class="noRecordsMessage">' . $this->objLanguage->languageText('mod_discussion_nopostsindiscussion', 'discussion') . '</div>';
                        } else {

                                // Else prepare a table to display results
                                $objTable = $this->getObject('htmltable', 'htmlelements');
                                $objTable->cellpadding = 5;
                                $objTable->cellspacing = 1;

                                $objTable->startHeaderRow();
                                $objTable->addHeaderCell($this->objLanguage->languageText('mod_discussion_topicconversation', 'discussion'));
                                $objTable->addHeaderCell($this->objLanguage->languageText('word_message'), '60%');
                                $objTable->addHeaderCell($this->objLanguage->languageText('word_author'), 100);
                                $objTable->endHeaderRow();

                                // Load Link class
                                $link = $this->loadClass('link', 'htmlelements');

                                // loop through posts
                                foreach ($results as $post) {
                                        $objTable->startRow();
                                        // title is link to the posts
                                        $titleLink = new link($this->uri(array('action' => 'viewtopic', 'id' => $post['topic_id'], 'type' => 'workgroup', 'post' => $post['post_id']), 'discussion'));
                                        $titleLink->link = $post['post_title'];
                                        $titleLink->anchor = $post['post_id'];
                                        $objTable->addCell($titleLink->show());


                                        // Chop the post to 130 characters to give user a glimpse of the post
                                        $text = stripslashes($this->trimstrObj->strTrim(strip_tags(trim($post['post_text'])), 130));
                                        // Additional Filter to remove line breaks
                                        $text = str_replace("\r\n", ' ', $text);

                                        $objTable->addCell($text);
                                        $objTable->addCell('<nobr>' . $post['firstname'] . ' ' . $post['surname'] . '</nobr>');
                                        $objTable->endRow();
                                }

                                // Return the finished table
                                return $objTable->show();
                        }
                }
        }

        /**
         * Method to display a post reply form
         * This is being standardised as a function to prevent duplication,
         * @param string $postId Record Id of the Post
         * @param boolean $showCancel Flag whether to show a Cancel button or not.
         * @return string Completed Form for posting a reply
         */
        function showPostReplyForm($postId, $showCancel = TRUE) {
                // Get the Post
                $post = $this->getPostWithText($postId);
                // Get details of the Discussion
                $discussion = $this->objDiscussion->getDiscussion($post['discussion_id']);


                // Do not show form if discussion is locked
                if ($discussion['discussionlocked'] == 'Y') {
                        return NULL;
                } else {
                        // Generate Temporary Id
                        $temporaryId = $this->objUser->userId() . '_' . time();

                        // Set Mode to New
                        $mode = 'new';

                        // Check if Title has Re: attached to it
                        if (substr($post['post_title'], 0, 3) == 'Re:') {
                                // If it does, simply strip slashes
                                $defaultTitle = stripslashes($post['post_title']);
                                $originalTitle = stripslashes($post['post_title']);
                        } else {
                                // Else strip slashes AND append Re: to the title
                                $defaultTitle = 'Re: ' . stripslashes($post['post_title']);
                                $originalTitle = 'Re: ' . stripslashes($post['post_title']);
                        }

                        // If result of server-side validation, change default title to posted one
                        if ($mode == 'fix') {
                                // Select Posted Title
                                $defaultTitle = $details['title'];
                        }

                        // Load Classes Needed
                        $this->loadClass('form', 'htmlelements');
                        $this->loadClass('textinput', 'htmlelements');
                        $this->loadClass('textarea', 'htmlelements');
                        $this->loadClass('button', 'htmlelements');
                        $this->loadClass('dropdown', 'htmlelements');
                        $this->loadClass('label', 'htmlelements');
                        $this->loadClass('radio', 'htmlelements');
                        $this->loadClass('htmlheading', 'htmlelements');
                        $this->loadClass('iframe', 'htmlelements');


                        // Start of Form
                        $postReplyForm = new form('postReplyForm', $this->uri(array('action' => 'savepostreply', 'type' => $this->discussiontype)));
                        $postReplyForm->displayType = 3;
                        $postReplyForm->addRule('title', $this->objLanguage->languageText('mod_discussion_addtitle', 'discussion'), 'required');


                        $addTable = $this->newObject('htmltable', 'htmlelements');
                        $addTable->width = '99%';
                        $addTable->align = 'center';
                        $addTable->cellpadding = 10;


                        $addTable->startRow();
                        $subjectLabel = new label($this->objLanguage->languageText('word_subject', 'system') . ':', 'input_title');
                        $addTable->addCell($subjectLabel->show(), 100);

                        $titleInput = new textinput('title');
                        $titleInput->size = 50;

                        $titleInput->value = htmlspecialchars($defaultTitle);

                        $addTable->addCell($titleInput->show());

                        $addTable->endRow();

                        // type of post
                        $addTable->startRow();

                        $addTable->addCell('<nobr>' . $this->objLanguage->languageText('mod_discussion_typeofreply', 'discussion') . ':</nobr>', 100);

                        $objElement = new radio('replytype');
                        $objElement->addOption('reply', $this->objLanguage->languageText('mod_discussion_postasreply', 'discussion'));
                        $objElement->addOption('tangent', $this->objLanguage->languageText('mod_discussion_postastangent', 'discussion'));
                        //$objElement->addOption('moderate','Post Reply as Moderator');

                        if ($mode == 'fix') {
                                $objElement->setSelected($details['replytype']);
                        } else {
                                $objElement->setSelected('reply');
                        }
                        $objElement->setBreakSpace('<br />');

                        $objElement->extra = ' onclick="clearForTangent()"';


                        $addTable->addCell($objElement->show());

                        $addTable->endRow();

                        $addTable->startRow();

                        $languageLabel = new label($this->objLanguage->languageText('word_language', 'system') . ':', 'input_language');
                        $addTable->addCell($languageLabel->show(), 100);

                        $languageList = new dropdown('language');
                        $languageCodes = & $this->getObject('languagecode', 'language');

                        // Sort Associative Array by Language, not ISO Code
                        asort($languageCodes->iso_639_2_tags->codes);

                        foreach ($languageCodes->iso_639_2_tags->codes as $key => $value) {
                                $languageList->addOption($key, $value);
                        }

                        $languageList->setSelected($languageCodes->getISO($this->objLanguage->currentLanguage()));

                        $addTable->addCell($languageList->show());

                        $addTable->endRow();

                        $addTable->startRow();

                        $addTable->addCell($this->objLanguage->languageText('word_message') . ':', 140);

                        $editor = &$this->newObject('htmlarea', 'htmlelements');
                        $editor->setName('message');
                        $editor->setContent('');
                        $editor->setRows(20);
                        $editor->setColumns('100');

                        $objContextCondition = &$this->getObject('contextcondition', 'contextpermissions');
                        $this->isContextLecturer = $objContextCondition->isContextMember('Lecturers');

                        if ($this->contextCode == '') {
                                $editor->context = FALSE;
                        } else if ($this->isContextLecturer || $objContextCondition->isAdmin()) {
                                $editor->context = TRUE;
                        } else {
                                $editor->context = FALSE;
                        }

                        $addTable->addCell($editor->show());

                        $addTable->endRow();

                        // ------------------

                        if ($discussion['attachments'] == 'Y') {
                                $addTable->startRow();

                                /*                $attachmentsLabel = new label($this->objLanguage->languageText('mod_discussion_attachments', 'discussion').':', 'attachments');
                                  $addTable->addCell($attachmentsLabel->show(), 100);

                                  $attachmentIframe = new iframe();
                                  $attachmentIframe->width='100%';
                                  $attachmentIframe->height='100';
                                  $attachmentIframe->frameborder='0';
                                  $attachmentIframe->src= $this->uri(array('module' => 'discussion', 'action' => 'attachments', 'id'=>$temporaryId, 'discussion' => $discussion['id'], 'type'=>$this->discussiontype));

                                  $addTable->addCell($attachmentIframe->show());
                                 */

                                $attachmentsLabel = new label($this->objLanguage->languageText('mod_discussion_attachments', 'discussion') . ':', 'attachments');
                                $addTable->addCell($attachmentsLabel->show(), 120);

                                $form = new form('saveattachment', $this->uri(array('action' => 'saveattachment')));

                                $objSelectFile = $this->newObject('selectfile', 'filemanager');
                                $objSelectFile->name = 'attachment';
                                $form->addToForm($objSelectFile->show());


                                $hiddenTypeInput = new textinput('discussionType');
                                $hiddenTypeInput->fldType = 'hidden';
                                $hiddenTypeInput->value = $post['type_id'];
                                $form->addToForm($hiddenTypeInput->show());


                                $hiddenTangentInput = new textinput('parent');
                                $hiddenTangentInput->fldType = 'hidden';
                                $hiddenTangentInput->value = $post['post_id'];
                                $form->addToForm($hiddenTangentInput->show());

                                $topicHiddenInput = new textinput('topic');
                                $topicHiddenInput->fldType = 'hidden';
                                $topicHiddenInput->value = $post['topic_id'];
                                $form->addToForm($topicHiddenInput->show());

                                $hiddenDiscussionInput = new textinput('discussion');
                                $hiddenDiscussionInput->fldType = 'hidden';
                                $hiddenDiscussionInput->value = $discussion['id'];
                                $form->addToForm($hiddenDiscussionInput->show());

                                $hiddenTemporaryId = new textinput('temporaryId');
                                $hiddenTemporaryId->fldType = 'hidden';
                                $hiddenTemporaryId->value = $temporaryId;
                                $form->addToForm($hiddenTemporaryId->show());

                                $addTable->addCell($form->show());
                                $addTable->endRow();
                        }

                        // ------------------------------
                        // Show Discussion Subscriptions if enabled

                        if ($discussion['subscriptions'] == 'Y') {
                                // Get the number of topics a user is subscribed to
//                                $numTopicSubscriptions = $this->objTopicSubscriptions->getNumTopicsSubscribed($post['discussion_id'], $this->objUser->userId());
//
//                                // Check whether the user is subscribed to the current topic
//                                $topicSubscription = $this->objTopicSubscriptions->isSubscribedToTopic($post['topic_id'], $this->objUser->userId());
//
//                                // Check whether the user is subscribed to the current discussion
//                                $discussionSubscription = $this->objDiscussionSubscriptions->isSubscribedToDiscussion($post['discussion_id'], $this->objUser->userId());
//
//                                $addTable->startRow();
//                                $addTable->addCell($this->objLanguage->languageText('mod_discussion_emailnotification', 'discussion', 'Email Notification') . ':');
////                                $subscriptionsRadio = new radio('subscriptions');
////                                $subscriptionsRadio->addOption('nosubscriptions', $this->objLanguage->languageText('mod_discussion_donotsubscribetothread', 'discussion', 'Do not subscribe to this thread'));
////                                $subscriptionsRadio->addOption('topicsubscribe', $this->objLanguage->languageText('mod_discussion_notifytopic', 'discussion', 'Notify me via email when someone replies to this thread'));
////                                $subscriptionsRadio->addOption('discussionsubscribe', $this->objLanguage->languageText('mod_discussion_notifydiscussion', 'discussion', 'Notify me of ALL new topics and replies in this discussion.'));
////                                $subscriptionsRadio->setBreakSpace('<br />');
//
//                                if ($discussionSubscription) {
//                                        $subscriptionsRadio->setSelected('discussionsubscribe');
//                                        $subscribeMessage = $this->objLanguage->languageText('mod_discussion_youaresubscribedtodiscussion', 'discussion', 'You are currently subscribed to the discussion, receiving notification of all new posts and replies.');
//                                } else if ($topicSubscription) {
//                                        $subscriptionsRadio->setSelected('topicsubscribe');
//                                        $subscribeMessage = $this->objLanguage->languageText('mod_discussion_youaresubscribedtotopic', 'discussion', 'You are already subscribed to this topic.');
//                                } else {
//                                        $subscriptionsRadio->setSelected('nosubscriptions');
//                                        $subscribeMessage = $this->objLanguage->languageText('mod_discussion_youaresubscribedtonumbertopic', 'discussion', 'You are currently subscribed to [NUM] topics.');
//                                        $subscribeMessage = str_replace('[NUM]', $numTopicSubscriptions, $subscribeMessage);
//                                }
//
//                                $div = '<div class="discussionTangentIndent">' . $subscribeMessage . '</div>';
//
//                                $addTable->addCell($subscriptionsRadio->show() . $div);
//                                $addTable->endRow();
                        }

                        // ------------------------------

                        $addTable->startRow();

                        $addTable->addCell(' ');

//                        $submitButton = new button('submitbutton', $this->objLanguage->languageText('word_submit'));
//                        $submitButton->cssClass = 'save';
//                        $submitButton->extra = ' onclick="SubmitForm()"';
                        //$submitButton->setToSubmit();
//                        if ($showCancel) {
//                                $cancelButton = new button('cancel', $this->objLanguage->languageText('word_cancel'));
//                                $returnUrl = $this->uri(array('action' => 'thread', 'id' => $post['topic_id'], 'type' => $this->discussiontype));
//                                $cancelButton->setOnClick("window.location='$returnUrl'");
//
//                                $addTable->addCell($submitButton->show() . ' / ' . $cancelButton->show());
//                        } else {
//
//
//                                $addTable->addCell($submitButton->show());
//                        }
//                        $addTable->endRow();
//
//                        $postReplyForm->addToForm($addTable);
                        // IE is not getting values from hidden textinputs...hence we pass them via session vars
//                        $this->setSession('temporaryId', $temporaryId);
//                        return $this->showTangentJavaScript($defaultTitle) . $postReplyForm->show();
                }
        }

        /**
         * Method to show the javascript used for warning the user if the title is the same when replying with a tangent
         *
         * @string $title Tit
         */
        function showTangentJavaScript($title) {
                $script = "
<script type=\"text/javascript\">
function clearForTangent()
{
    postTitle = \"" . addslashes($title) . "\";


    if (document.forms[\"postReplyForm\"].replytype[1].checked)
    {

        if (document.forms[\"postReplyForm\"].title.value == \"" . addslashes($title) . "\".split(\"'\").join(\"\'\"))
        {
            alert ('" . $this->objLanguage->languageText('mod_discussion_tangentsowntitles', 'discussion') . " \"" . addslashes($title) . "\".\\n" . $this->objLanguage->languageText('mod_discussion_changetitle', 'discussion') . ".');
            document.forms[\"postReplyForm\"].title.value = '';
            document.forms[\"postReplyForm\"].title.focus();


        }
    }

    if (document.forms[\"postReplyForm\"].replytype[0].checked)
    {
        if (document.forms[\"postReplyForm\"].title.value == '')
        {
            document.forms[\"postReplyForm\"].title.value = postTitle.split(\"'\").join(\"\'\");
            document.forms[\"postReplyForm\"].title.focus();
        }
    }


}
</script>

        ";

                return $script;
        }

        /**
         * Method to get the Record Id of the first post in a topic
         * @param string $topic_id Record Id of the Topic
         * @return string Id if it exists, else FALSE
         */
        function getIdFirstPostInTopic($topic_id) {
                $sql = 'SELECT id FROM tbl_discussion_post WHERE topic_id=\'' . $topic_id . '\' ORDER BY post_order LIMIT 1';
                $results = $this->getArray($sql);

                if ((is_countable($results) ? count($results) : 0) > 0) {
                        return $results[0]['id'];
                } else {
                        return FALSE;
                }
        }

        /**
         * Method to move a post from one tangent to another.
         * @param string $originalTopic Record Id of the Topic which needs to be updated
         * @param string $newTopic Topic to whom the tangents will be moved to
         */
        function movePostTangent($originalTopic, $newTopic) {
                $originalPostId = $this->getIdFirstPostInTopic($originalTopic);
                $newPostId = $this->getIdFirstPostInTopic($newTopic);

                return $this->update('id', $originalPostId, array('post_tangent_parent' => $newPostId));
        }

        /**
         * Method to remove a tangent setting from a post
         * @param string $topic Record Id of the Topic
         */
        function removeTangentParent($topic) {
                $post = $this->getIdFirstPostInTopic($topic);

                return $this->update('id', $post, array('post_tangent_parent' => '0'));
        }

        /**
         * Method to get the discussion details of a post
         * @param string $post_id Record Id of the post
         * @return array Details of the discussion
         */
        function getPostDiscussionDetails($post_id) {
                $sql = 'SELECT tbl_discussion.* FROM tbl_discussion_post
        INNER JOIN tbl_discussion_topic ON ( tbl_discussion_post.topic_id = tbl_discussion_topic.id)
        INNER JOIN tbl_discussion ON ( tbl_discussion_topic.discussion_id = tbl_discussion.id)
        WHERE tbl_discussion_post.id = \'' . $post_id . '\' GROUP BY tbl_discussion_post.id LIMIT 1';

                $discussion = $this->getArray($sql);

                if ((is_countable($discussion) ? count($discussion) : 0) == 1) {
                        return $discussion[0];
                } else {
                        return FALSE;
                }
        }

        /**
         * Function to Check that the Left and Right Values of a Tree are in tact.
         * This is related to the modified preorder tree traversal algorithm.
         * If the topic is 'broken', it calls the function to fix it.
         * It does a whole series of checks to ensure the topic is not broken
         *
         * @param string $topic Record Id of the Topic
         */
        function detectBrokenTopic($topic) {

                // Get Root Post
                $rootPost = $this->getRootPost($topic);

                // Start Broken Detection
                // Left value of first post MUST be one
                if ($rootPost['postleft'] != 1) {
                        // If not, topic is broken - start fixing tree
                        $this->rebuildTopic($topic);
                        return;
                        /* CHISIMBA_PHP8_DISCUSSION_STRAY_BREAK: break outside loop is invalid on PHP 8. */
                        return false;
                }

                // Left value of first post MUST be one
                if ($rootPost['post_order'] != 1) {
                        // If not, topic is broken - start fixing tree
                        $this->rebuildTopic($topic);
                        return;
                        /* CHISIMBA_PHP8_STRAY_BREAK: invalid outside loop/switch. */
                        return false;
                }

                // Right value of first post can never be zero
                if ($rootPost['postright'] == 0) {
                        // If not, topic is broken - start fixing tree
                        $this->rebuildTopic($topic);
                        return;
                        /* CHISIMBA_PHP8_STRAY_BREAK: invalid outside loop/switch. */
                        return false;
                }

                // Right value of first post can never be less than left value
                if ($rootPost['postright'] < $rootPost['postleft']) {
                        // If not, topic is broken - start fixing tree
                        $this->rebuildTopic($topic);
                        return;
                        /* CHISIMBA_PHP8_STRAY_BREAK: invalid outside loop/switch. */
                        return false;
                }

                // Right value of first post can never be an odd number
                if ($rootPost['postright'] % 2 != 0) {
                        // If not, topic is broken - start fixing tree
                        $this->rebuildTopic($topic);
                        return;
                        /* CHISIMBA_PHP8_STRAY_BREAK: invalid outside loop/switch. */
                        return false;
                }

                // Get Num Children
                $numChildren = $this->getRecordCount(' WHERE topic_id = "' . $topic . '"');

                // Right Value of First Post divided by two equals the number of topics in the post.
                if ($rootPost['postright'] / 2 != $numChildren) {
                        // If not, topic is broken - start fixing tree
                        $this->rebuildTopic($topic);
                        return;
                        /* CHISIMBA_PHP8_ILLEGAL_BREAK: parser-confirmed stray break. */
                        return false;
                }

                // Check if there are any duplicate Left Values
                $leftSql = 'SELECT t1.id  FROM tbl_discussion_post t1, tbl_discussion_post t2 WHERE (t1.lft = t2.lft) AND t1.topic_id = \'' . $topic . '\' AND t2.topic_id = \'' . $topic . '\' AND t1.id != t2.id GROUP BY t1.lft';
                $leftResults = $this->getArray($leftSql);

                if ((is_countable($leftResults) ? count($leftResults) : 0) > 0) {
                        // If not, topic is broken - start fixing tree
                        $this->rebuildTopic($topic);
                        return;
                        /* CHISIMBA_PHP8_ILLEGAL_BREAK: parser-confirmed stray break. */
                        return false;
                }

                // Check if there are any duplicate Right Values
                $rightSql = 'SELECT t1.id  FROM tbl_discussion_post t1, tbl_discussion_post t2 WHERE (t1.rght = t2.rght) AND t1.topic_id = \'' . $topic . '\' AND t2.topic_id = \'' . $topic . '\' AND t1.id != t2.id GROUP BY t1.lft';
                $rightResults = $this->getArray($rightSql);

                if ((is_countable($rightResults) ? count($rightResults) : 0) > 0) {
                        // If not, topic is broken - start fixing tree
                        $this->rebuildTopic($topic);
                        return;
                        /* CHISIMBA_PHP8_ILLEGAL_BREAK: parser-confirmed stray break. */
                        return false;
                }

                // If not, topic is not broken
                return;
        }

        /**
         * Method to start a process of rebuilding a discussion topic tree
         * @param string $topic Record Id of the Topic
         */
        function rebuildTopic($topic) {
                // Get Root Post
                $rootPost = $this->getRootPost($topic);

                // Check if Valid Topic
                if ($rootPost != FALSE) {
                        $this->_rebuild_tree($rootPost['post_id'], 1, 1);
                }

                return;
        }

        /**
         * Method to Rebuild a Tree
         *
         * This function recursives itself to update the left and right values of a tree
         *
         * @access private
         * @param string $parent Record Id of the Parent post
         * @param int $left Left Value of the Parent
         * @param int $level Level of the Post
         */
        function _rebuild_tree($parent, $left, $level) {
                // the right value of this node is the left value + 1
                $right = $left + 1;

                // get all children of this node
                $result = $this->getAll(' WHERE  post_parent=\'' . $parent . '\'');

                foreach ($result as $row) {
                        $right = $this->_rebuild_tree($row['id'], $right, $level + 1);
                }

                //echo 'Id - '.$parent.', left - '.$left.', right - '.$right.'<br />';

                $this->update('id', $parent, array('lft' => $left, 'rght' => $right, 'level' => $level));

                // return the right value of this node + 1
                return $right + 1;
        }

        /**
         * Method to Build a get the Replies to a post and build them as a tree
         * JavaScript automatically sent to header
         * @param string $post_id Record Id of the Post
         * @return string DHTML Treee
         */
        function buildChildTree($post_id) {
                $post = $this->getRow('id', $post_id);

                $childPosts = $this->getChildPostsSQL($post['topic_id'], $post['lft'], $post['rght']);

                $this->loadClass('treemenu', 'tree');
                $this->loadClass('treenode', 'tree');
                $this->loadClass('dhtml', 'tree');
                $this->loadClass('htmllist', 'tree');

                $treeMenu = new treemenu();

                $nodeArray = array();

                foreach ($childPosts as $childPost) {
                        $node = new treenode($this->generateNodeText($childPost));
                        $nodeArray[$childPost['post_id']] = & $node;

                        if ($childPost['post_parent'] == $post_id) {
                                $treeMenu->addItem($node);
                        } else {
                                if (array_key_exists($childPost['post_parent'], $nodeArray)) {
                                        $nodeArray[$childPost['post_parent']]->addItem($node);
                                }
                        }
                }

                $tree = new htmllist($treeMenu, array('images' => $this->objSkin->getSkinURL() . 'treeimages/imagesAlt2'), FALSE);

                //$this->appendArrayVar('headerParams', '<script src="modules/tree/resources/TreeMenu.js" language="JavaScript" type="text/javascript"></script>');
                return $tree->getMenu();
        }

        function getChildPostsSQL($topic, $left, $right) {
                $sql = 'SELECT tbl_discussion_post.*, tbl_discussion_post_text.*, tbl_users.firstname, tbl_users.surname FROM tbl_discussion_post
        INNER JOIN tbl_discussion_post_text ON ( tbl_discussion_post_text.post_id = tbl_discussion_post.id AND tbl_discussion_post_text.original_post=\'1\')
        LEFT  JOIN tbl_users ON ( tbl_discussion_post.userId = tbl_users.userId )
        WHERE topic_id=\'' . $topic . '\' AND lft>' . $left . ' AND rght<' . $right . ' ORDER BY lft';

                return $this->getArray($sql);
        }

        function deletePostAndReplies($post_id) {
                $post = $this->getRow('id', $post_id);

                $childPosts = $this->getChildPostsSQL($post['topic_id'], $post['lft'], $post['rght']);

                foreach ($childPosts as $childPost) {
                        $this->delete('id', $childPost['post_id']);
                }

                $this->delete('id', $post_id);

                $this->rebuildTopic($post['topic_id']);

                return;
        }

        /**
         * Method to return the javascript used for ajax translation of posts
         * @return string
         */
        function getTranslationAjaxScript() {
                return '<script type="text/javascript">
//<![CDATA[

function loadTranslation(post, lang) {
    var textElement = document.getElementById(\'text_\' + post);
    var loadingElement = document.getElementById(\'loading_\' + post);
    if (!textElement || !loadingElement) {
        return;
    }

    var parameters = new URLSearchParams({
        module: \'discussion\',
        action: \'loadtranslation\',
        id: post,
        lang: lang
    });

    textElement.style.display = \'none\';
    loadingElement.textContent = \'Loading translation…\';
    loadingElement.style.display = \'block\';

    fetch(\'index.php?\' + parameters.toString(), {
        method: \'GET\',
        credentials: \'same-origin\',
        headers: {\'X-Requested-With\': \'XMLHttpRequest\'}
    })
        .then(function (response) {
            if (!response.ok) {
                throw new Error(\'Translation request failed\');
            }
            return response.text();
        })
        .then(function (html) {
            textElement.innerHTML = html;
            textElement.style.display = \'block\';
            loadingElement.style.display = \'none\';
        })
        .catch(function () {
            textElement.style.display = \'block\';
            loadingElement.textContent = \'Unable to load translation.\';
        });
}

//]]>
</script>';
        }

        /**
         * Method to get the last post in a topic
         * @param string $topicid Record Id of the Topic
         * @return string Record Id of the last post
         */
        function getLastTopicPost($topicid) {
                $results = $this->getAll(' WHERE topic_id = "' . $topicid . '" ORDER BY tbl_discussion_post.dateLastUpdated DESC LIMIT 1');

                if ((is_countable($results) ? count($results) : 0) == 0) {
                        return FALSE;
                } else {
                        return $results[0]['id'];
                }
        }

        /**
         * Method to get the amount of posts in a topic
         * @param string $topicid Record Id of the Topic
         * @return int Number of Posts
         */
        function getNumPostsInTopic($topicid) {
                return $this->getRecordCount(' WHERE topic_id = "' . $topicid . '"');
        }

        /**
         * Insert a post into the database
         *
         * @param string $post_parent: Record ID of the Parent Post - that the user is replying to
         * @param string $post_tangent_parent: Record ID of the Tangent Post - that the user is replying to
         * @param string $post_title: Title of Post
         * @param string $post_message: Text of the Post
         * @param string $topic_id: Record ID of the Topic
         * @param string $userId: User ID of person posting the post
         * @param string $dateLastUpdated: Date Post was made
         * @return string $this->getLastInsertId()
         */
        function insertSingleAPI($post_parent, $post_tangent_parent, $discussion_id, $topic_id, $userId, $level = 1) {
                // Interim measure. Alternative, use regexp and replace with space
                //$post_title = strip_tags($post_title);
                //echo $post_parent;

                if ($post_parent == '0') {
                        // $lastRightPointer = $this->getLastRightPointer($discussion_id);
                        //  $leftPointer = $lastRightPointer+1;
                        //  $rightPointer = $lastRightPointer+2;
                        $level = 1;
                } else {
                        /*  $lastRightPointer = $this->getPostRightPointer($post_parent);
                          $updateRightSQL = 'UPDATE tbl_discussion_post SET rght = rght + 2 WHERE rght > '.($lastRightPointer-1);
                          $this->getArray($updateRightSQL);
                          $updateLeftSQL = 'UPDATE tbl_discussion_post SET lft = lft + 2 WHERE lft > '.($lastRightPointer-1);
                          $this->getArray($updateLeftSQL);

                          $leftPointer = $lastRightPointer;
                          $rightPointer = $lastRightPointer+1; */
                        $level += 1;
                }

                $this->insert(array(
                    'post_parent' => $post_parent,
                    'post_tangent_parent' => $post_tangent_parent,
                    'topic_id' => $topic_id,
                    'post_order' => $this->getLastPostOrder($topic_id),
                    'userId' => $userId,
                    'lft' => null,
                    'rght' => null,
                    'level' => $level,
                    'datelastupdated' => strftime('%Y-%m-%d %H:%M:%S', time())
                ));

                return $this->getLastInsertId();
        }

        /** Return all contributions; bound individual excerpts, never silently omit later posts. */
        public function getAssessmentEvidence($discussionId, $userId) {
                $discussion = str_replace("'", "''", (string) $discussionId);
                $learner = str_replace("'", "''", (string) $userId);
                $sql = "SELECT p.id AS post_id,p.topic_id,p.datecreated,t.post_title,t.post_text "
                        . "FROM tbl_discussion_post p INNER JOIN tbl_discussion_topic topic ON topic.id=p.topic_id "
                        . "INNER JOIN tbl_discussion_post_text t ON t.post_id=p.id "
                        . "INNER JOIN tbl_users u ON (p.userid=u.id OR p.userid=u.userid) "
                        . "WHERE topic.discussion_id='" . $discussion . "' AND (u.id='" . $learner . "' OR u.userid='" . $learner . "') "
                        . "AND t.original_post IN ('1','Y') ORDER BY p.datecreated ASC,p.id ASC,t.id ASC";
                $evidence = array();
                foreach ((array) $this->getArray($sql) as $row) {
                        $evidence[] = array(
                                'postId'=>(string) $row['post_id'],
                                'topicId'=>(string) $row['topic_id'],
                                'date'=>(string) $row['datecreated'],
                                'title'=>mb_substr(trim(strip_tags((string) $row['post_title'])), 0, 160),
                                'text'=>mb_substr(trim(strip_tags((string) $row['post_text'])), 0, 8000),
                                'revision'=>hash('sha256', (string)$row['post_title'] . "\0" . (string)$row['post_text']),
                        );
                }
                return $evidence;
        }
}

?>
